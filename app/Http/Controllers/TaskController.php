<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;

class TaskController extends Controller
{
    /**
     * US8 — Liste des tâches d'un projet (avec assigné chargé).
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $project->load('tasks.user');

        // Évite N+1 sur @can('update', $task) qui appelle $task->project->isLead()
        $project->tasks->each->setRelation('project', $project);

        return view('tasks.index', [
            'project' => $project,
            'tasks'   => $project->tasks,
        ]);
    }

    /**
     * US9 — formulaire de création (lead).
     */
    public function create(Project $project)
    {
        $this->authorize('createTask', $project);

        $project->load('users');

        return view('tasks.create', [
            'project' => $project,
            'members' => $project->users,
        ]);
    }

    /**
     * US9 — enregistrement d'une tâche.
     */
    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->authorize('createTask', $project);

        $project->tasks()->create([
            ...$request->validated(),
            'status' => 'todo',
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche créée.');
    }

    /**
     * Détail d'une tâche.
     */
    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $task);

        $task->load('user');
        $task->setRelation('project', $project); // évite reload pour @can

        return view('tasks.show', compact('project', 'task'));
    }

    /**
     * US10 — formulaire d'édition (lead).
     */
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $project->load('users');

        return view('tasks.edit', [
            'project' => $project,
            'task'    => $task,
            'members' => $project->users,
        ]);
    }

    /**
     * US10 — mise à jour complète (lead).
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche mise à jour.');
    }

    /**
     * US11 — le developer assigné change uniquement le statut.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Task $task)
    {
        $this->authorize('updateStatus', $task);

        $task->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Statut mis à jour.');
    }

    /**
     * US12 — suppression (lead).
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche supprimée.');
    }
}
