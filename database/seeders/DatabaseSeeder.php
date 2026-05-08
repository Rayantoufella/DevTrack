<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 20 users (password = "password" pour tous)
        $users = User::factory()->count(20)->create();

        // 15 projets, chacun avec 1 lead + 2-4 developers + 5-10 tâches
        Project::factory()->count(15)->create()->each(function (Project $project) use ($users) {
            // Lead aléatoire
            $lead = $users->random();

            // 2-4 developers (exclus le lead pour éviter les doublons pivot)
            $developers = $users
                ->where('id', '!=', $lead->id)
                ->random(rand(2, 4));

            // Promotion : un user qui dirige un projet devient lead globalement.
            if ($lead->role !== 'lead') {
                $lead->update(['role' => 'lead']);
            }

            // Attache le lead
            $project->users()->attach($lead->id, [
                'role'        => 'lead',
                'assigned_at' => now(),
            ]);

            // Attache les developers
            foreach ($developers as $dev) {
                $project->users()->attach($dev->id, [
                    'role'        => 'developer',
                    'assigned_at' => now(),
                ]);
            }

            // 5-10 tâches par projet, chacune assignée à un membre du projet
            $members = collect([$lead])->merge($developers);

            Task::factory()
                ->count(rand(5, 10))
                ->state(fn () => [
                    'project_id' => $project->id,
                    'user_id'    => $members->random()->id,
                ])
                ->create();
        });
    }
}
