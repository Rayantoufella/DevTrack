<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    // Check if the user is a member of this project
    private function isMember(User $user, Project $project)
    {
        return $project->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    // Check if the user is the lead of this project
    private function isLeadOfProject(User $user, Project $project)
    {
        return $project->users()
            ->where('user_id', $user->id)
            ->where('project_user.role', 'lead')
            ->exists();
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Project $project)
    {
        return $this->isMember($user, $project);
    }

    public function create(User $user)
    {
        return $user->isLead();
    }

    public function update(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }

    public function delete(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }

    public function restore(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }

    public function forceDelete(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }

    public function manageMembers(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }

    public function createTask(User $user, Project $project)
    {
        return $this->isLeadOfProject($user, $project);
    }
}
