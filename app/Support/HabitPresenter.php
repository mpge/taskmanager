<?php

namespace App\Support;

use App\Enums\HabitCadence;
use App\Models\Habit;
use App\Models\HabitEntry;
use Carbon\CarbonImmutable;

final class HabitPresenter
{
    /**
     * How many days of history to expose for the contribution grid (~20 weeks).
     */
    private const int GRID_DAYS = 139;

    public function __construct(private readonly StreakCalculator $streaks) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Habit $habit, CarbonImmutable $today): array
    {
        $dates = $habit->entries
            ->map(fn (HabitEntry $entry): string => $entry->entry_date->toDateString())
            ->all();

        $gridStart = $today->subDays(self::GRID_DAYS)->toDateString();
        $recent = array_values(array_filter($dates, fn (string $date): bool => $date >= $gridStart));

        return [
            'id' => $habit->id,
            'name' => $habit->name,
            'cadence' => $habit->cadence->value,
            'target_per_period' => $habit->target_per_period,
            'color' => $habit->color,
            'icon' => $habit->icon,
            'position' => $habit->position,
            'current_streak' => $this->streaks->current($dates, $today, $habit->cadence, $habit->target_per_period),
            'longest_streak' => $this->streaks->longest($dates, $habit->cadence, $habit->target_per_period),
            'done_today' => $this->doneThisPeriod($dates, $today, $habit->cadence, $habit->target_per_period),
            'entries' => $recent,
        ];
    }

    /**
     * @param  iterable<int, Habit>  $habits
     * @return array<int, array<string, mixed>>
     */
    public function collection(iterable $habits, CarbonImmutable $today): array
    {
        $out = [];

        foreach ($habits as $habit) {
            $out[] = $this->present($habit, $today);
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function doneThisPeriod(array $dates, CarbonImmutable $today, HabitCadence $cadence, int $target): bool
    {
        return match ($cadence) {
            HabitCadence::Daily => in_array($today->toDateString(), $dates, true),
            HabitCadence::Weekly => collect($dates)->filter(
                fn (string $date): bool => CarbonImmutable::parse($date)->startOfWeek()->equalTo($today->startOfWeek()),
            )->count() >= $target,
        };
    }
}
