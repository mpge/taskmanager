<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    /**
     * Persist the user's timezone (auto-detected from the browser).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $validated = $request->validate([
            'timezone' => ['required', 'timezone:all'],
        ]);

        $user->update(['timezone' => $validated['timezone']]);

        return back();
    }
}
