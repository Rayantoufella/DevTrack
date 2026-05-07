<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * US2 — tout user authentifié peut voir son dashboard de projets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Voir un projet : il faut être membre (lead ou developer).
     */
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user);
    }

    /**
     * US3 — tout user authentifié peut créer un projet (et devient lead).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * US4 — seul le lead peut modifier.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }

    /**
     * US5 — seul le lead peut archiver.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }

    /**
     * US6 — seul le lead peut restaurer.
     */
    public function restore(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }

    /**
     * Bonus — seul le lead peut supprimer définitivement.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }

    /**
     * US7 — seul le lead peut ajouter/retirer des membres.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }

    /**
     * US9 — seul le lead peut créer des tâches dans le projet.
     */
    public function createTask(User $user, Project $project): bool
    {
        return $project->isLead($user);
    }
}
