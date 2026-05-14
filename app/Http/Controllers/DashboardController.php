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

        // 1) Get the user's projects with task counts
        $projects = $user->projects()
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'done');
            }])
            ->withCount(['tasks as urgent_tasks_count' => function ($query) {
                $query->urgent();
            }])
            ->with('users:id,name')
            ->orderBy('deadline')
            ->get();

        $projectIds = $projects->pluck('id');

        // 2) Week range (this week and last week)
        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();
        $prevStart = now()->startOfWeek()->subWeek();

        // 3) Main stats
        $activeTasks = Task::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'done')
            ->count();

        $doneThisWeek = Task::whereIn('project_id', $projectIds)
            ->where('status', 'done')
            ->whereBetween('updated_at', [$weekStart, $weekEnd])
            ->count();

        $crew = User::whereHas('projects', function ($query) use ($projectIds) {
            $query->whereIn('projects.id', $projectIds);
        })->distinct()->count('users.id');

        $urgent = Task::whereIn('project_id', $projectIds)->urgent()->count();

        $dueWeek = Task::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'done')
            ->whereBetween('deadline', [now(), $weekEnd])
            ->count();

        $stats = [
            'projects' => $projects->count(),
            'active'   => $activeTasks,
            'done'     => $doneThisWeek,
            'crew'     => $crew,
            'urgent'   => $urgent,
            'due_week' => $dueWeek,
        ];

        // 4) Compare this week to last week (in %)
        $donePrev = Task::whereIn('project_id', $projectIds)
            ->where('status', 'done')
            ->whereBetween('updated_at', [$prevStart, $weekStart])
            ->count();

        if ($donePrev > 0) {
            $weekDelta = round((($doneThisWeek - $donePrev) / $donePrev) * 100);
        } elseif ($doneThisWeek > 0) {
            $weekDelta = 100;
        } else {
            $weekDelta = 0;
        }

        // 5) Filters: how many projects as lead / developer
        $filters = [
            'all'  => $projects->count(),
            'lead' => $projects->where('pivot.role', 'lead')->count(),
            'dev'  => $projects->where('pivot.role', 'developer')->count(),
        ];

        // 6) Velocity = global progress bar
        $totalTasks = Task::whereIn('project_id', $projectIds)->count();
        $totalDone  = Task::whereIn('project_id', $projectIds)->where('status', 'done')->count();
        $totalProg  = Task::whereIn('project_id', $projectIds)->where('status', 'in_progress')->count();
        $totalTodo  = max(0, $totalTasks - $totalDone - $totalProg);

        $pct = 0;
        $pctProg = 0;
        $pctTodo = 0;
        if ($totalTasks > 0) {
            $pct     = round($totalDone / $totalTasks * 100);
            $pctProg = round($totalProg / $totalTasks * 100);
            $pctTodo = round($totalTodo / $totalTasks * 100);
        }

        $velocity = [
            'total'    => $totalTasks,
            'done'     => $totalDone,
            'pct'      => $pct,
            'pct_prog' => $pctProg,
            'pct_todo' => $pctTodo,
        ];

        // 7) Is the user a lead in at least one project?
        $isLead = $filters['lead'] > 0;

        return view('dashboard', compact(
            'projects', 'stats', 'filters', 'velocity', 'weekDelta', 'isLead'
        ));
    }
}
