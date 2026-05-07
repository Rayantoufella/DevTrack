<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * US8 — voir une tâche : il faut être membre du projet parent.
     */
    public function view(User $user, Task $task): bool
    {
        return $task->project->isMember($user);
    }

    /**
     * US10 — modifier une tâche complète : seul le lead du projet.
     */
    public function update(User $user, Task $task): bool
    {
        return $task->project->isLead($user);
    }

    /**
     * US12 — supprimer une tâche : seul le lead du projet.
     */
    public function delete(User $user, Task $task): bool
    {
        return $task->project->isLead($user);
    }

    /**
     * US11 — changer uniquement le statut : seul le developer assigné à la tâche.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }
}
