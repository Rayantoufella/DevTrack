<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Task;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory()->count(10)->create();
        $projects = Project::factory()->count(10)->create();


        $projects->each(function($project) use ($users){
            $project->users()->attach($users->random(3) ,['role' => 'developer']);

            $project->users()->attach($users->first(),['role'=> 'lead']);
        });

        Task::factory()->count(20)->create();
        
    }
}
