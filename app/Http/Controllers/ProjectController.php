<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * US2 — Dashboard : projets de l'utilisateur (lead ou developer)
     * avec total tâches et tâches terminées.
     */
    public function index()
    {
        $this->authorize('viewAny', Project::class);

        $projects = Auth::user()
            ->projects()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
            ])
            ->get();

        return view('projects.index', compact('projects'));
    }

    /**
     * US3 — formulaire de création.
     */
    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    /**
     * US3 — enregistrement + l'utilisateur devient lead.
     */
    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);

        $project = Project::create($request->validated());

        $project->users()->attach(Auth::id(), [
            'role'        => 'lead',
            'assigned_at' => now(),
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé.');
    }

    /**
     * US8 (partiel) — détail du projet avec tâches et membres.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['users', 'tasks.user']);

        // Évite N+1 : chaque @can('update', $task) appelle $task->project->isLead()
        // sans cette ligne, $task->project ferait une requête supplémentaire par tâche.
        $project->tasks->each->setRelation('project', $project);

        return view('projects.show', compact('project'));
    }

    /**
     * US4 — formulaire de modification.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    /**
     * US4 — mise à jour.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Projet mis à jour.');
    }

    /**
     * US5 — archive (soft delete).
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet archivé.');
    }

    /**
     * US5/US6 — Liste des projets archivés du lead courant.
     */
    public function archives()
    {
        $this->authorize('viewAny', Project::class);

        $projects = Auth::user()
            ->projects()
            ->wherePivot('role', 'lead')
            ->onlyTrashed()
            ->withCount('tasks')
            ->get();

        return view('projects.archives', compact('projects'));
    }

    /**
     * US6 — Restaurer un projet archivé.
     */
    public function restore(Project $project)
    {
        $this->authorize('restore', $project);

        $project->restore();

        return redirect()
            ->route('projects.archives')
            ->with('success', 'Projet restauré.');
    }

    /**
     * Bonus — Suppression définitive depuis la page Archives.
     */
    public function forceDelete(Project $project)
    {
        $this->authorize('forceDelete', $project);

        $project->forceDelete();

        return redirect()
            ->route('projects.archives')
            ->with('success', 'Projet supprimé définitivement.');
    }
}
