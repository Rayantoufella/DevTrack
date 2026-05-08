<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{

    public function viewAny(User $user): bool
    {
        return true;
    }


    public function view(User $user, Project $project): bool
    {
        return $project->user()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }


    public function update(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }


    public function restore(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }


    public function manageMembers(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }

    public function createTask(User $user, Project $project): bool
    {
        return $project->user()
        ->where('user_id', $user->id)
        ->where('role', 'lead')
        ->exists();
    }
}
