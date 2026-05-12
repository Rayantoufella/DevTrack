<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $projects = $user->projects()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
                'tasks as urgent_tasks_count'    => fn ($q) => $q->urgent(),
            ])
            ->with(['users:id,name'])
            ->orderBy('deadline')
            ->get();

        $projectIds = $projects->pluck('id');
        $weekStart  = now()->startOfWeek();
        $weekEnd    = now()->endOfWeek();
        $prevStart  = (clone $weekStart)->subWeek();

        $stats = [
            'projects' => $projects->count(),
            'active'   => Task::whereIn('project_id', $projectIds)->where('status', '!=', 'done')->count(),
            'done'     => Task::whereIn('project_id', $projectIds)->where('status', 'done')
                              ->whereBetween('updated_at', [$weekStart, $weekEnd])->count(),
            'crew'     => User::whereHas('projects', fn ($q) => $q->whereIn('projects.id', $projectIds))
                              ->distinct()->count('users.id'),
            'urgent'   => Task::whereIn('project_id', $projectIds)->urgent()->count(),
            'due_week' => Task::whereIn('project_id', $projectIds)
                              ->where('status', '!=', 'done')
                              ->whereBetween('deadline', [now(), $weekEnd])->count(),
        ];

        $donePrev = Task::whereIn('project_id', $projectIds)
            ->where('status', 'done')
            ->whereBetween('updated_at', [$prevStart, $weekStart])
            ->count();

        $weekDelta = $donePrev > 0
            ? round((($stats['done'] - $donePrev) / $donePrev) * 100)
            : ($stats['done'] > 0 ? 100 : 0);

        $filters = [
            'all'  => $projects->count(),
            'lead' => $projects->where('pivot.role', 'lead')->count(),
            'dev'  => $projects->where('pivot.role', 'developer')->count(),
        ];

        $totalTasks = Task::whereIn('project_id', $projectIds)->count();
        $totalDone  = Task::whereIn('project_id', $projectIds)->where('status', 'done')->count();
        $totalProg  = Task::whereIn('project_id', $projectIds)->where('status', 'in_progress')->count();
        $totalTodo  = max(0, $totalTasks - $totalDone - $totalProg);

        $velocity = [
            'total'    => $totalTasks,
            'done'     => $totalDone,
            'pct'      => $totalTasks > 0 ? round($totalDone / $totalTasks * 100) : 0,
            'pct_prog' => $totalTasks > 0 ? round($totalProg / $totalTasks * 100) : 0,
            'pct_todo' => $totalTasks > 0 ? round($totalTodo / $totalTasks * 100) : 0,
        ];

        $isLead = $projects->contains(fn ($p) => ($p->pivot->role ?? null) === 'lead');

        return view('dashboard', compact(
            'projects', 'stats', 'filters', 'velocity', 'weekDelta', 'isLead'
        ));
    }
}
