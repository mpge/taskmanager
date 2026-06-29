<?php

namespace App\Support;

use App\Models\Task;

final class TaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'notes' => $task->notes,
            'bucket' => $task->bucket->value,
            'status' => $task->status->value,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'position' => $task->position,
        ];
    }

    /**
     * @param  iterable<int, Task>  $tasks
     * @return array<int, array<string, mixed>>
     */
    public function collection(iterable $tasks): array
    {
        $out = [];

        foreach ($tasks as $task) {
            $out[] = $this->present($task);
        }

        return $out;
    }
}
