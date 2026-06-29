<?php

namespace App\Enums;

enum InsightType: string
{
    case Overdue = 'overdue';
    case StreakAtRisk = 'streak_at_risk';
    case OverloadedImportant = 'overloaded_important';
    case Focus = 'focus';
    case WeeklyReview = 'weekly_review';

    /**
     * A coarse tone the UI uses to colour the insight.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Overdue, self::StreakAtRisk => 'warning',
            self::OverloadedImportant, self::WeeklyReview => 'info',
            self::Focus => 'positive',
        };
    }

    /**
     * Display order: the more urgent, the lower the number.
     */
    public function order(): int
    {
        return match ($this) {
            self::Overdue => 0,
            self::StreakAtRisk => 1,
            self::OverloadedImportant => 2,
            self::Focus => 3,
            self::WeeklyReview => 4,
        };
    }
}
