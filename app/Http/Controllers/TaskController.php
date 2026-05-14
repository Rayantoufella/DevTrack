<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;

class TaskController extends Controller
{
    // Show all tasks of a project
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $project->load('tasks.user');

        return view('tasks.index', [
            'project' => $project,
            'tasks'   => $project->tasks,
        ]);
    }

    // Form to create a new task (only lead)
    public function create(Project $project)
    {
        $this->authorize('createTask', $project);

        $project->load('users');

        return view('tasks.create', [
            'project' => $project,
            'members' => $project->users,
        ]);
    }

    // Save the new task
    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->authorize('createTask', $project);

        $data = $request->validated();
        $data['status'] = 'todo';

        $project->tasks()->create($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche créée.');
    }

    // Show one task
    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $task);

        $task->load('user');

        return view('tasks.show', compact('project', 'task'));
    }

    // Form to edit a task (only lead)
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

    // Update the task (only lead)
    public function update(UpdateTaskRequest $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche mise à jour.');
    }

    // The developer changes only the status of his task
    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Task $task)
    {
        $this->authorize('updateStatus', $task);

        $task->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Statut mis à jour.');
    }

    // Delete the task (only lead)
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Tâche supprimée.');
    }
}
