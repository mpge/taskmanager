<?php

namespace App\Http\Controllers;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Support\HabitPresenter;
use App\Support\Insight;
use App\Support\InsightService;
use App\Support\TaskPresenter;
use App\Support\TaskRanker;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly InsightService $insights,
        private readonly TaskPresenter $taskPresenter,
        private readonly HabitPresenter $habitPresenter,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        \assert($user instanceof User);

        $today = CarbonImmutable::now($user->timezone);

        // Load once and share with InsightService to avoid re-querying.
        $openTasks = $user->tasks()->where('status', TaskStatus::Open)->get();

        $habits = $user->habits()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->with('entries')
            ->get();

        $insights = array_map(
            fn (Insight $insight): array => $insight->toArray(),
            $this->insights->for($user, $today, $openTasks, $habits),
        );

        $importantOpen = $openTasks
            ->filter(fn (Task $task): bool => $task->bucket === TaskBucket::Important)
            ->all();
        $focus = array_slice(TaskRanker::focusOrder($importantOpen), 0, 5);

        $presentedHabits = $this->habitPresenter->collection($habits, $today);
        $habitsDoneToday = count(array_filter(
            $presentedHabits,
            fn (array $habit): bool => $habit['done_today'] === true,
        ));

        return Inertia::render('Dashboard', [
            'today' => $today->toDateString(),
            'greetingName' => $user->name,
            'insights' => $insights,
            'focus' => $this->taskPresenter->collection($focus),
            'habits' => $presentedHabits,
            'stats' => [
                'important_open' => count($importantOpen),
                'eventual_open' => $openTasks->filter(fn (Task $task): bool => $task->bucket === TaskBucket::Eventual)->count(),
                'habits_total' => count($presentedHabits),
                'habits_done_today' => $habitsDoneToday,
            ],
        ]);
    }
}
