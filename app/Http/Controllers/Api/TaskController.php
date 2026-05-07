<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;

class TaskController extends Controller
{
    /**
     * US13 — GET /api/projects/{project}/tasks
     * Retourne les tâches d'un projet sérialisées avec TaskResource.
     */
    public function index(Project $project)
    {
        $project->load('tasks.user');

        return TaskResource::collection($project->tasks);
    }
}
