<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{

    public function view(User $user, Task $task)
    {
        return $task->project->users()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Task $task)
    {
        return $task->project->users()->where('user_id', $user->id)->where('role', 'lead')->exists();
    }


    public function delete(User $user, Task $task)
    {
        return $task->project->users()->where('user_id', $user->id)->where('role', 'lead')->exists(); 
    }
    public function updateStatus(User $user, Task $task)
    {
        return $task->user_id === $user->id;
    }
}
