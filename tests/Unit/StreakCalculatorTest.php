<?php

use App\Enums\HabitCadence;
use App\Support\StreakCalculator;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->calc = new StreakCalculator;
});

it('returns zero when there are no entries', function () {
    expect($this->calc->current([], CarbonImmutable::parse('2026-06-28'), HabitCadence::Daily))->toBe(0)
        ->and($this->calc->longest([], HabitCadence::Daily))->toBe(0);
});

it('counts a daily streak ending today', function () {
    $dates = ['2026-06-26', '2026-06-27', '2026-06-28'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Daily))->toBe(3);
});

it('keeps the daily streak alive when today is not done yet but yesterday was', function () {
    $dates = ['2026-06-26', '2026-06-27'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Daily))->toBe(2);
});

it('breaks the daily streak when the last entry is more than a day ago', function () {
    $dates = ['2026-06-24', '2026-06-25'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Daily))->toBe(0);
});

it('ignores duplicate entries on the same day', function () {
    $dates = ['2026-06-28', '2026-06-28', '2026-06-27'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Daily))->toBe(2);
});

it('finds the longest daily run', function () {
    $dates = ['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-10', '2026-06-11'];

    expect($this->calc->longest($dates, HabitCadence::Daily))->toBe(3);
});

it('counts a weekly streak across consecutive weeks', function () {
    // Week starting Mon 2026-06-15, week starting Mon 2026-06-22 (06-28 is the Sunday of it).
    $dates = ['2026-06-15', '2026-06-22', '2026-06-28'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Weekly))->toBe(2);
});

it('keeps a weekly streak alive when the current week is not yet done', function () {
    // Today's week (Mon 06-22) has no entry, but the two prior weeks do.
    $dates = ['2026-06-08', '2026-06-15'];

    expect($this->calc->current($dates, CarbonImmutable::parse('2026-06-28'), HabitCadence::Weekly))->toBe(2);
});

it('requires meeting the weekly target for a week to count', function () {
    $today = CarbonImmutable::parse('2026-06-28');

    // One entry in the current week, target of 2 -> week not complete -> streak 0.
    expect($this->calc->current(['2026-06-28'], $today, HabitCadence::Weekly, 2))->toBe(0);

    // Two entries in the current week, target of 2 -> complete -> streak 1.
    expect($this->calc->current(['2026-06-27', '2026-06-28'], $today, HabitCadence::Weekly, 2))->toBe(1);
});

it('only counts weeks meeting the target toward the longest streak', function () {
    // Week of 06-15 has 2 entries (meets target 2); week of 06-22 has 1 (does not).
    $dates = ['2026-06-15', '2026-06-16', '2026-06-22'];

    expect($this->calc->longest($dates, HabitCadence::Weekly, 2))->toBe(1);
});
