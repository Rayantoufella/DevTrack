<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(['todo', 'in_progress', 'done']),
            'priority'    => fake()->randomElement(['low', 'medium', 'high']),
            'deadline'    => fake()->dateTimeBetween('now', '+1 month'),
            'project_id'  => Project::inRandomOrder()->value('id') ?? Project::factory(),
            'user_id'     => User::inRandomOrder()->value('id')    ?? User::factory(),
        ];
    }
}
