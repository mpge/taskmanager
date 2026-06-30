<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows access without a subscription when billing is disabled', function () {
    config(['billing.enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

it('redirects to billing when enabled and the user is not subscribed', function () {
    config(['billing.enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('billing.show'));
});

it('allows access when enabled and the user has an active subscription', function () {
    config(['billing.enabled' => true]);

    $user = User::factory()->create();
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('does not gate the billing page itself for unsubscribed users', function () {
    config(['billing.enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('billing.show'))
        ->assertOk();
});
