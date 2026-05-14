<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    // Show all projects of the connected user
    public function index()
    { //kat3yt 3la lfile policies
        $this->authorize('viewAny', Project::class);

        $projects = Auth::user()
            ->projects()
->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
                'tasks as urgent_tasks_count'    => fn ($q) => $q->urgent(),
            ])
            ->with('users:id,name')
            ->orderBy('deadline')
            ->get();

        return view('projects.index', compact('projects'));
    }

    // Form to create a new project
    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    // Save the new project and make the user the lead
    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);
//validated() returns only the validated fields 
        $project = Project::create($request->validated());
//Insert a row in the pivot table project_user with 
// role = lead and assigned_at = now()
//  This makes the creator the lead of the project. 
        $project->users()->attach(Auth::id(), [
            'role'        => 'lead',
            'assigned_at' => now(),
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé.');
    }

    // Show one project with its tasks and members
    public function show(Project $project)
    {
        ##Only members of the project can see it.
        $this->authorize('view', $project);

        $project->load(['users', 'tasks.user']);

        // Find the role of the current user inside this project
        $member = $project->users->firstWhere('id', Auth::id());
        $role = $member ? $member->pivot->role : 'developer';

        $stats = $project->taskStats();

        return view('projects.show', compact('project', 'role', 'stats'));
    }

    // Form to edit a project
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    // Update the project
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Projet mis à jour.');
    }

    // Archive the project (soft delete)
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet archivé.');
    }

    // Show archived projects of the current lead
    public function archives()
    {
        $this->authorize('viewAny', Project::class);

        $projects = Auth::user()
            ->projects()
            ->wherePivot('role', 'lead')
            ->onlyTrashed()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
            ])
            ->with('users')
            ->get();

        return view('projects.archives', compact('projects'));
    }

    // Restore an archived project
    public function restore(Project $project)
    {
        $this->authorize('restore', $project);

        $project->restore();

        return redirect()
            ->route('projects.archives')
            ->with('success', 'Projet restauré.');
    }

    // Delete a project permanently
    public function forceDelete(Project $project)
    {
        $this->authorize('forceDelete', $project);

        $project->forceDelete();

        return redirect()
            ->route('projects.archives')
            ->with('success', 'Projet supprimé définitivement.');
    }
}
