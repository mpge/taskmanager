<?php

namespace App\Support;

use App\Enums\HabitCadence;
use Carbon\CarbonImmutable;

/**
 * Computes habit streaks from a list of completion dates.
 *
 * A "period" is a day for daily habits and an ISO week (Mon-Sun) for weekly
 * habits. A period counts as completed when it contains at least `$target`
 * entries (1 by default, so daily habits and single-target weekly habits behave
 * as a simple "done at least once").
 */
class StreakCalculator
{
    /**
     * The number of consecutive completed periods ending at the current period.
     *
     * If the current period is not yet completed the streak is still considered
     * alive from the previous period (so "not done today, yet" doesn't read as a
     * broken streak until the period actually lapses).
     *
     * @param  array<int, string>  $dates  completion dates as 'Y-m-d'
     */
    public function current(array $dates, CarbonImmutable $today, HabitCadence $cadence, int $target = 1): int
    {
        $completed = $this->completedPeriods($dates, $cadence, $target);

        if ($completed === []) {
            return 0;
        }

        $set = array_flip($completed);
        $cursor = $this->periodStart($today, $cadence);

        if (! isset($set[$this->key($cursor)])) {
            $cursor = $this->step($cursor, $cadence, -1);

            if (! isset($set[$this->key($cursor)])) {
                return 0;
            }
        }

        $streak = 0;

        while (isset($set[$this->key($cursor)])) {
            $streak++;
            $cursor = $this->step($cursor, $cadence, -1);
        }

        return $streak;
    }

    /**
     * The longest run of consecutive completed periods on record.
     *
     * @param  array<int, string>  $dates  completion dates as 'Y-m-d'
     */
    public function longest(array $dates, HabitCadence $cadence, int $target = 1): int
    {
        $completed = $this->completedPeriods($dates, $cadence, $target);

        if ($completed === []) {
            return 0;
        }

        $starts = array_map(
            fn (string $key): CarbonImmutable => CarbonImmutable::parse($key),
            $completed,
        );
        usort($starts, fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        $longest = 1;
        $run = 1;

        for ($i = 1, $count = count($starts); $i < $count; $i++) {
            $expected = $this->step($starts[$i - 1], $cadence, 1);

            $run = $this->key($starts[$i]) === $this->key($expected) ? $run + 1 : 1;
            $longest = max($longest, $run);
        }

        return $longest;
    }

    /**
     * Period-start keys ('Y-m-d') for periods with at least `$target` entries.
     *
     * @param  array<int, string>  $dates
     * @return array<int, string>
     */
    private function completedPeriods(array $dates, HabitCadence $cadence, int $target): array
    {
        $counts = [];

        foreach ($dates as $date) {
            $key = $this->key($this->periodStart(CarbonImmutable::parse($date), $cadence));
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $completed = [];

        foreach ($counts as $key => $count) {
            if ($count >= $target) {
                $completed[] = $key;
            }
        }

        return $completed;
    }

    private function periodStart(CarbonImmutable $date, HabitCadence $cadence): CarbonImmutable
    {
        return match ($cadence) {
            HabitCadence::Daily => $date->startOfDay(),
            HabitCadence::Weekly => $date->startOfWeek(),
        };
    }

    private function step(CarbonImmutable $periodStart, HabitCadence $cadence, int $amount): CarbonImmutable
    {
        return match ($cadence) {
            HabitCadence::Daily => $periodStart->addDays($amount),
            HabitCadence::Weekly => $periodStart->addWeeks($amount),
        };
    }

    private function key(CarbonImmutable $periodStart): string
    {
        return $periodStart->format('Y-m-d');
    }
}
