<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Créi 1 user dyal test bach t-loggini bih
        $user = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@test.com',
            'role'  => 'lead',
        ]);

        // 2) Créi 3 projets w attachihom l-user b role "lead"
        Project::factory()->count(3)->create()->each(function ($project) use ($user) {
            $project->users()->attach($user->id, [
                'role'        => 'lead',
                'assigned_at' => now(),
            ]);

            // 3) Kol projet 3ando 5 tasks assignées l-user
            Task::factory()->count(5)->create([
                'project_id' => $project->id,
                'user_id'    => $user->id,
            ]);
        });
    }
}
