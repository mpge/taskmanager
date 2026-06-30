<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BillingController extends Controller
{
    /**
     * Show the subscription / billing page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        \assert($user instanceof User);

        return Inertia::render('Billing', [
            'billingEnabled' => (bool) config('billing.enabled'),
            'subscribed' => $user->subscribed('default'),
            'onTrial' => $user->onTrial('default'),
            'price' => (string) config('billing.display_price'),
            'interval' => (string) config('billing.display_interval'),
            'trialDays' => (int) config('billing.trial_days'),
        ]);
    }

    /**
     * Start a Stripe Checkout session for the subscription.
     */
    public function checkout(Request $request): SymfonyResponse
    {
        abort_unless((bool) config('billing.enabled') && (bool) config('billing.price_id'), 404);

        $user = $request->user();
        \assert($user instanceof User);

        $subscription = $user->newSubscription('default', (string) config('billing.price_id'));

        if ((int) config('billing.trial_days') > 0) {
            $subscription->trialDays((int) config('billing.trial_days'));
        }

        $checkout = $subscription->checkout([
            'success_url' => route('dashboard'),
            'cancel_url' => route('billing.show'),
        ]);

        // Hand the Stripe Checkout URL to Inertia as an external redirect.
        return Inertia::location($checkout->redirect()->getTargetUrl());
    }

    /**
     * Redirect to the Stripe billing portal to manage the subscription.
     */
    public function portal(Request $request): RedirectResponse
    {
        abort_unless((bool) config('billing.enabled'), 404);

        $user = $request->user();
        \assert($user instanceof User);

        return $user->redirectToBillingPortal(route('billing.show'));
    }
}
