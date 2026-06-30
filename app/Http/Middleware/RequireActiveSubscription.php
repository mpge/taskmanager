<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * When billing is enabled (the hosted plan), an active subscription is
     * required to reach the app. Off by default so self-hosting stays free.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('billing.enabled')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User && ! $user->subscribed('default')) {
            abort_if($request->expectsJson(), 402, 'An active subscription is required.');

            return redirect()->route('billing.show');
        }

        return $next($request);
    }
}
