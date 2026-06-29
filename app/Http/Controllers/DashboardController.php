<?php

namespace App\Http\Controllers;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Support\HabitPresenter;
use App\Support\Insight;
use App\Support\InsightService;
use App\Support\TaskPresenter;
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

        $today = CarbonImmutable::now();

        $focus = $user->tasks()
            ->where('status', TaskStatus::Open)
            ->where('bucket', TaskBucket::Important)
            ->orderByDesc('priority')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('position')
            ->limit(5)
            ->get();

        $habits = $user->habits()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->with('entries')
            ->get();

        $insights = array_map(
            fn (Insight $insight): array => $insight->toArray(),
            $this->insights->for($user, $today),
        );

        $presentedHabits = $this->habitPresenter->collection($habits, $today);
        $habitsDoneToday = count(array_filter(
            $presentedHabits,
            fn (array $habit): bool => $habit['done_today'] === true,
        ));

        return Inertia::render('Dashboard', [
            'today' => $today->toIso8601String(),
            'greetingName' => $user->name,
            'insights' => $insights,
            'focus' => $this->taskPresenter->collection($focus),
            'habits' => $presentedHabits,
            'stats' => [
                'important_open' => $user->tasks()->where('status', TaskStatus::Open)->where('bucket', TaskBucket::Important)->count(),
                'eventual_open' => $user->tasks()->where('status', TaskStatus::Open)->where('bucket', TaskBucket::Eventual)->count(),
                'habits_total' => count($presentedHabits),
                'habits_done_today' => $habitsDoneToday,
            ],
        ]);
    }
}
