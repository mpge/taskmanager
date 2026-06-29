<?php

use App\Enums\TaskBucket;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('moves a task to the bottom of the target lane when switching buckets', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['bucket' => TaskBucket::Important, 'position' => 0]);
    Task::factory()->for($user)->eventual()->create(['position' => 5]);

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), ['bucket' => 'eventual'])
        ->assertRedirect();

    $task->refresh();
    expect($task->bucket)->toBe(TaskBucket::Eventual)
        ->and($task->position)->toBe(6);
});
