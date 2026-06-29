<?php

namespace App\Support;

use App\Models\Task;

final class TaskRanker
{
    /**
     * Order tasks for "what should I focus on": priority desc, then due date
     * ascending (undated last), then manual position. Shared by the dashboard
     * focus list and the InsightService focus nudge so the rule lives once.
     *
     * @param  array<int, Task>  $tasks
     * @return list<Task>
     */
    public static function focusOrder(array $tasks): array
    {
        $ordered = array_values($tasks);

        usort($ordered, function (Task $a, Task $b): int {
            if ($a->priority !== $b->priority) {
                return $b->priority <=> $a->priority;
            }

            $aDue = $a->due_date?->toDateString() ?? '9999-12-31';
            $bDue = $b->due_date?->toDateString() ?? '9999-12-31';

            return $aDue !== $bDue ? $aDue <=> $bDue : $a->position <=> $b->position;
        });

        return $ordered;
    }
}
