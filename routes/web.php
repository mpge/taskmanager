<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitEntryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimezoneController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Billing is reachable without an active subscription so users can subscribe.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

Route::middleware(['auth', 'verified', 'subscribed'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('habits', [HabitController::class, 'index'])->name('habits.index');
    Route::post('habits', [HabitController::class, 'store'])->name('habits.store');
    Route::post('habits/reorder', [HabitController::class, 'reorder'])->name('habits.reorder');
    Route::patch('habits/{habit}', [HabitController::class, 'update'])->name('habits.update');
    Route::delete('habits/{habit}', [HabitController::class, 'destroy'])->name('habits.destroy');
    Route::post('habits/{habit}/toggle', [HabitEntryController::class, 'toggle'])->name('habits.toggle');

    Route::patch('preferences/timezone', [TimezoneController::class, 'update'])->name('preferences.timezone');
});

require __DIR__.'/settings.php';
