<?php

namespace App\Support;

use App\Enums\HabitCadence;
use App\Enums\InsightType;
use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\Habit;
use App\Models\HabitEntry;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Deterministic, rule-based nudges. No AI, no randomness — given the same data
 * and date, it always returns the same ordered list of insights.
 */
class InsightService
{
    /**
     * Above this many open Important tasks, the lane is considered overloaded.
     */
    private const int IMPORTANT_OVERLOAD_THRESHOLD = 7;

    /**
     * How many tasks to name in the daily focus.
     */
    private const int FOCUS_LIMIT = 3;

    public function __construct(private readonly StreakCalculator $streaks) {}

    /**
     * @return array<int, Insight>
     */
    public function for(User $user, CarbonImmutable $today): array
    {
        /** @var Collection<int, Task> $openTasks */
        $openTasks = $user->tasks()->where('status', TaskStatus::Open)->get();

        /** @var Collection<int, Habit> $habits */
        $habits = $user->habits()->where('is_active', true)->with('entries')->get();

        $insights = array_filter([
            $this->overdue($openTasks, $today),
            $this->streakAtRisk($habits, $today),
            $this->overloadedImportant($openTasks),
            $this->focus($openTasks),
            $this->weeklyReview($openTasks, $today),
        ]);

        usort($insights, fn (Insight $a, Insight $b): int => $a->type->order() <=> $b->type->order());

        return $insights;
    }

    /**
     * @param  Collection<int, Task>  $openTasks
     */
    private function overdue(Collection $openTasks, CarbonImmutable $today): ?Insight
    {
        $count = $openTasks
            ->filter(fn (Task $task): bool => $task->due_date !== null
                && $task->due_date->toDateString() < $today->toDateString())
            ->count();

        if ($count === 0) {
            return null;
        }

        return new Insight(
            InsightType::Overdue,
            $count === 1 ? '1 task is overdue' : "{$count} tasks are overdue",
            'These slipped past their due date. Knock them out or push the date so they stop nagging.',
        );
    }

    /**
     * @param  Collection<int, Task>  $openTasks
     */
    private function overloadedImportant(Collection $openTasks): ?Insight
    {
        $count = $openTasks->filter(fn (Task $task): bool => $task->bucket === TaskBucket::Important)->count();

        if ($count <= self::IMPORTANT_OVERLOAD_THRESHOLD) {
            return null;
        }

        return new Insight(
            InsightType::OverloadedImportant,
            'Your Important lane is full',
            "You have {$count} Important tasks. Move a few to Eventual so today stays focused.",
        );
    }

    /**
     * @param  Collection<int, Task>  $openTasks
     */
    private function focus(Collection $openTasks): ?Insight
    {
        $important = $openTasks->filter(fn (Task $task): bool => $task->bucket === TaskBucket::Important)->all();

        usort($important, function (Task $a, Task $b): int {
            if ($a->priority !== $b->priority) {
                return $b->priority <=> $a->priority;
            }

            $aDue = $a->due_date?->toDateString() ?? '9999-12-31';
            $bDue = $b->due_date?->toDateString() ?? '9999-12-31';

            return $aDue !== $bDue ? $aDue <=> $bDue : $a->position <=> $b->position;
        });

        $top = array_slice($important, 0, self::FOCUS_LIMIT);

        if ($top === []) {
            return null;
        }

        $titles = implode(', ', array_map(fn (Task $task): string => $task->title, $top));

        return new Insight(
            InsightType::Focus,
            "Today's focus",
            "Start with: {$titles}.",
        );
    }

    /**
     * @param  Collection<int, Habit>  $habits
     */
    private function streakAtRisk(Collection $habits, CarbonImmutable $today): ?Insight
    {
        $atRisk = [];

        foreach ($habits as $habit) {
            if ($habit->cadence !== HabitCadence::Daily) {
                continue;
            }

            $dates = $habit->entries
                ->map(fn (HabitEntry $entry): string => $entry->entry_date->toDateString())
                ->all();

            $doneToday = in_array($today->toDateString(), $dates, true);
            $streak = $this->streaks->current($dates, $today, $habit->cadence);

            if ($streak >= 2 && ! $doneToday) {
                $atRisk[] = "{$habit->name} ({$streak} days)";
            }
        }

        if ($atRisk === []) {
            return null;
        }

        return new Insight(
            InsightType::StreakAtRisk,
            'Keep your streak alive',
            'Not done yet today: '.implode(', ', $atRisk).'.',
        );
    }

    /**
     * @param  Collection<int, Task>  $openTasks
     */
    private function weeklyReview(Collection $openTasks, CarbonImmutable $today): ?Insight
    {
        if ($today->dayOfWeek !== CarbonImmutable::SUNDAY) {
            return null;
        }

        $count = $openTasks->filter(fn (Task $task): bool => $task->bucket === TaskBucket::Eventual)->count();

        if ($count === 0) {
            return null;
        }

        return new Insight(
            InsightType::WeeklyReview,
            'Time for a weekly review',
            "Good day to revisit your {$count} Eventual task(s) and pull anything important forward.",
        );
    }
}
