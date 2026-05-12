<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth');

// US13 — Endpoint API tâches d'un projet
Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])
    ->name('api.projects.tasks.index');
