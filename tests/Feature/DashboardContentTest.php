<?php

use App\Models\Habit;
use App\Models\HabitEntry;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders Today with focus ordering, stats, and habits-done-today', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-25 10:00', 'UTC')); // Thursday

    $user = User::factory()->create(['timezone' => 'UTC']);

    Task::factory()->for($user)->create(['title' => 'High prio', 'priority' => 3]);
    Task::factory()->for($user)->create(['title' => 'Due soon', 'priority' => 0, 'due_date' => '2026-06-26']);
    Task::factory()->for($user)->create(['title' => 'Whenever', 'priority' => 0]);
    Task::factory()->for($user)->eventual()->create(['title' => 'Later thing']);

    $done = Habit::factory()->for($user)->create(['name' => 'Done one']);
    HabitEntry::factory()->for($done)->on('2026-06-25')->create();
    Habit::factory()->for($user)->create(['name' => 'Not done']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('today', '2026-06-25')
            ->where('stats.important_open', 3)
            ->where('stats.eventual_open', 1)
            ->where('stats.habits_total', 2)
            ->where('stats.habits_done_today', 1)
            ->has('focus', 3)
            ->where('focus.0.title', 'High prio')
            ->where('focus.1.title', 'Due soon')
            ->where('focus.2.title', 'Whenever')
        );
});
