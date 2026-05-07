<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddProjectMemberRequest;
use App\Models\Project;
use App\Models\User;

class ProjectMemberController extends Controller
{
    /**
     * US7 — Ajouter un developer au projet via son email.
     */
    public function store(AddProjectMemberRequest $request, Project $project)
    {
        $this->authorize('manageMembers', $project);

        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $project->users()->attach($user->id, [
            'role'        => 'developer',
            'assigned_at' => now(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Membre {$user->name} ajouté.");
    }

    /**
     * US7 — Retirer un membre du projet.
     */
    public function destroy(Project $project, User $user)
    {
        $this->authorize('manageMembers', $project);

        $project->users()->detach($user->id);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Membre {$user->name} retiré.");
    }
}
