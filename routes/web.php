<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('projects.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Profil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // US5/US6 + bonus — Archives (avant la resource pour éviter la collision avec /projects/{project})
    Route::get('projects/archives', [ProjectController::class, 'archives'])
        ->name('projects.archives');
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])
        ->withTrashed()
        ->name('projects.restore');
    Route::delete('projects/{project}/force', [ProjectController::class, 'forceDelete'])
        ->withTrashed()
        ->name('projects.forceDelete');

    // US2-US5 — Projets (CRUD + archive via destroy soft-delete)
    Route::resource('projects', ProjectController::class);

    // US7 — Membres d'un projet
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])
        ->name('projects.members.store');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');

    // US11 — Changement de statut par le developer assigné (avant la resource pour ne pas être masqué)
    Route::patch('projects/{project}/tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('projects.tasks.updateStatus');

    // US8-US12 — Tâches (nested sous projects)
    Route::resource('projects.tasks', TaskController::class);

});

require __DIR__.'/auth.php';
