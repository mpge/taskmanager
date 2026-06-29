<?php

use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('files a habit toggle on the user local day, not the server UTC day', function () {
    // 02:00 UTC on the 29th is still the 28th in US Pacific (UTC-7 in summer).
    $this->travelTo(CarbonImmutable::parse('2026-06-29 02:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)->post(route('habits.toggle', $habit))->assertRedirect();

    expect($habit->entries()->first()->entry_date->toDateString())->toBe('2026-06-28');
});

it('lets a Pacific user check in on their local today even when UTC has rolled over', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-29 02:00', 'UTC'));

    $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
    $habit = Habit::factory()->for($user)->create();

    // 2026-06-28 is "today" for the user, so it must be accepted (not rejected as future).
    $this->actingAs($user)
        ->post(route('habits.toggle', $habit), ['date' => '2026-06-28'])
        ->assertRedirect();

    expect($habit->entries()->count())->toBe(1);
});

it('persists a valid user timezone', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->patch(route('preferences.timezone'), ['timezone' => 'Europe/Paris'])
        ->assertRedirect();

    expect($user->refresh()->timezone)->toBe('Europe/Paris');
});

it('rejects an invalid timezone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('preferences.timezone'), ['timezone' => 'Mars/Olympus'])
        ->assertSessionHasErrors('timezone');
});
