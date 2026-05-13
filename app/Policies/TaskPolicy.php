<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    // Check if user is the lead of the task's project
    private function isLeadOfProject(User $user, Task $task)
    {
        return $task->project->users()
            ->where('user_id', $user->id)
            ->where('project_user.role', 'lead')
            ->exists();
    }

    public function view(User $user, Task $task)
    {
        return $task->project->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, Task $task)
    {
        return $this->isLeadOfProject($user, $task);
    }

    public function delete(User $user, Task $task)
    {
        return $this->isLeadOfProject($user, $task);
    }

    public function updateStatus(User $user, Task $task)
    {
        return $task->user_id === $user->id;
    }
}
