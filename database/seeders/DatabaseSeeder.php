<?php

namespace Database\Seeders;

use App\Enums\HabitCadence;
use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@tend.app'],
            [
                'name' => 'Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => CarbonImmutable::now(),
            ],
        );

        if ($user->tasks()->exists() || $user->habits()->exists()) {
            return;
        }

        $today = CarbonImmutable::now();

        $this->seedTasks($user, $today);
        $this->seedHabits($user, $today);
    }

    private function seedTasks(User $user, CarbonImmutable $today): void
    {
        $important = [
            ['title' => 'Finish the project proposal', 'priority' => 3, 'due' => 1],
            ['title' => 'Call the dentist back', 'priority' => 1, 'due' => -2],
            ['title' => 'Renew passport', 'priority' => 2, 'due' => 6],
            ['title' => 'Pay the credit card', 'priority' => 2, 'due' => 4],
            ['title' => 'Reply to Sam about the weekend', 'priority' => 0, 'due' => null],
        ];

        foreach ($important as $position => $row) {
            $user->tasks()->create([
                'title' => $row['title'],
                'bucket' => TaskBucket::Important,
                'status' => TaskStatus::Open,
                'priority' => $row['priority'],
                'due_date' => $row['due'] === null ? null : $today->addDays($row['due'])->toDateString(),
                'position' => $position,
            ]);
        }

        $eventual = [
            'Learn to bake sourdough',
            'Organize the photo library',
            'Plan a summer road trip',
            'Read the book on my nightstand',
            'Try the new café downtown',
        ];

        foreach ($eventual as $position => $title) {
            $user->tasks()->create([
                'title' => $title,
                'bucket' => TaskBucket::Eventual,
                'status' => TaskStatus::Open,
                'position' => $position,
            ]);
        }
    }

    private function seedHabits(User $user, CarbonImmutable $today): void
    {
        $habits = [
            ['name' => 'Morning walk', 'color' => '#2f9e57', 'cadence' => HabitCadence::Daily, 'skip' => 6, 'streak' => 9],
            ['name' => 'Read 20 minutes', 'color' => '#3f7fb0', 'cadence' => HabitCadence::Daily, 'skip' => 3, 'streak' => 3],
            ['name' => 'Drink water', 'color' => '#2f9e8f', 'cadence' => HabitCadence::Daily, 'skip' => 7, 'streak' => 5],
            ['name' => 'Stretch', 'color' => '#e0892f', 'cadence' => HabitCadence::Daily, 'skip' => 2, 'streak' => 0],
            ['name' => 'Meditate', 'color' => '#7b5cd6', 'cadence' => HabitCadence::Weekly, 'skip' => 4, 'streak' => 0],
        ];

        foreach ($habits as $position => $row) {
            /** @var Habit $habit */
            $habit = $user->habits()->create([
                'name' => $row['name'],
                'color' => $row['color'],
                'cadence' => $row['cadence'],
                'target_per_period' => $row['cadence'] === HabitCadence::Weekly ? 3 : 1,
                'position' => $position,
            ]);

            $this->seedEntries($habit, $today, $row['skip'], $row['streak']);
        }
    }

    private function seedEntries(Habit $habit, CarbonImmutable $today, int $skip, int $streak): void
    {
        $dates = [];

        // A loose historical pattern for the contribution grid.
        for ($d = 5; $d <= 90; $d++) {
            if ($d % $skip !== 0) {
                $dates[] = $today->subDays($d)->toDateString();
            }
        }

        // A clean current streak ending yesterday (so "today" is still open).
        for ($d = 1; $d <= $streak; $d++) {
            $dates[] = $today->subDays($d)->toDateString();
        }

        foreach (array_unique($dates) as $date) {
            $habit->entries()->create(['entry_date' => $date]);
        }
    }
}
