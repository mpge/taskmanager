<?php

use App\Enums\InsightType;
use App\Models\Habit;
use App\Models\HabitEntry;
use App\Models\Task;
use App\Models\User;
use App\Support\Insight;
use App\Support\InsightService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array<int, InsightType>
 */
function insightTypes(User $user, string $today = '2026-06-25'): array
{
    return array_map(
        fn (Insight $insight): InsightType => $insight->type,
        app(InsightService::class)->for($user, CarbonImmutable::parse($today)),
    );
}

it('returns no insights for an empty account', function () {
    $user = User::factory()->create();

    expect(app(InsightService::class)->for($user, CarbonImmutable::parse('2026-06-25')))->toBe([]);
});

it('flags overdue open tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['due_date' => '2026-06-20']);
    Task::factory()->for($user)->create(['due_date' => '2026-06-21']);
    // Not overdue / not open — must be ignored.
    Task::factory()->for($user)->create(['due_date' => '2026-07-10']);
    Task::factory()->for($user)->done()->create(['due_date' => '2026-06-01']);

    expect(insightTypes($user))->toContain(InsightType::Overdue);
});

it('does not flag overdue when nothing is past due', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['due_date' => null]);
    Task::factory()->for($user)->create(['due_date' => '2026-07-10']);

    expect(insightTypes($user))->not->toContain(InsightType::Overdue);
});

it('warns when the important lane is overloaded', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(8)->create();

    expect(insightTypes($user))->toContain(InsightType::OverloadedImportant);
});

it('does not warn about overload with a reasonable number of important tasks', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->count(5)->create();

    expect(insightTypes($user))->not->toContain(InsightType::OverloadedImportant);
});

it('suggests a focus drawn from the important lane', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'File taxes', 'priority' => 3]);

    $insights = app(InsightService::class)->for($user, CarbonImmutable::parse('2026-06-25'));
    $focus = collect($insights)->firstOrFail(fn (Insight $i): bool => $i->type === InsightType::Focus);

    expect($focus->message)->toContain('File taxes');
});

it('flags a daily habit streak that is at risk today', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['name' => 'Exercise']);
    // Streak on the 23rd and 24th, but not yet done on the 25th.
    HabitEntry::factory()->for($habit)->on('2026-06-23')->create();
    HabitEntry::factory()->for($habit)->on('2026-06-24')->create();

    $insights = app(InsightService::class)->for($user, CarbonImmutable::parse('2026-06-25'));
    $risk = collect($insights)->firstOrFail(fn (Insight $i): bool => $i->type === InsightType::StreakAtRisk);

    expect($risk->message)->toContain('Exercise');
});

it('does not flag a streak that was already done today', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();
    HabitEntry::factory()->for($habit)->on('2026-06-23')->create();
    HabitEntry::factory()->for($habit)->on('2026-06-24')->create();
    HabitEntry::factory()->for($habit)->on('2026-06-25')->create();

    expect(insightTypes($user))->not->toContain(InsightType::StreakAtRisk);
});

it('suggests a weekly review on Sundays when eventual tasks exist', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->eventual()->create();

    // 2026-06-28 is a Sunday.
    expect(insightTypes($user, '2026-06-28'))->toContain(InsightType::WeeklyReview);
});

it('does not suggest a weekly review midweek', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->eventual()->create();

    expect(insightTypes($user, '2026-06-25'))->not->toContain(InsightType::WeeklyReview);
});

it('orders urgent insights ahead of the daily focus', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Important thing', 'due_date' => '2026-06-01']);

    $types = insightTypes($user);
    $overdueAt = array_search(InsightType::Overdue, $types, true);
    $focusAt = array_search(InsightType::Focus, $types, true);

    expect($overdueAt)->toBeLessThan($focusAt);
});
