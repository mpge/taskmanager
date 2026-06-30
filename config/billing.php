<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Billing Enabled
    |--------------------------------------------------------------------------
    |
    | When true, an active subscription is required to use the app (enforced by
    | the RequireActiveSubscription middleware). Off by default so self-hosting
    | stays free; the hosted plan turns it on with Stripe credentials set.
    |
    */

    'enabled' => env('BILLING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Subscription Price
    |--------------------------------------------------------------------------
    |
    | The Stripe Price ID customers are subscribed to at checkout. The display
    | figures below are presentational only (the real amount lives in Stripe).
    |
    */

    'price_id' => env('STRIPE_PRICE_ID'),

    'display_price' => env('BILLING_DISPLAY_PRICE', '$5'),

    'display_interval' => env('BILLING_DISPLAY_INTERVAL', 'month'),

    /*
    |--------------------------------------------------------------------------
    | Trial Days
    |--------------------------------------------------------------------------
    |
    | Number of trial days granted at signup before the first charge. Zero
    | means no trial.
    |
    */

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 0),

];
