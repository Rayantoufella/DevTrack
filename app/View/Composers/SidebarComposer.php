<?php

namespace App\View\Composers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view)
    {
        // If badges already set, do nothing
        if ($view->offsetExists('sidebarBadges')) {
            return;
        }

        $user = Auth::user();

        // If no user is connected, do nothing
        if (!$user) {
            return;
        }

        $projectsCount = $user->projects()->count();

        $tasksCount = Task::where('user_id', $user->id)
            ->where('status', '!=', 'done')
            ->count();

        $archivesCount = $user->projects()
            ->wherePivot('role', 'lead')
            ->onlyTrashed()
            ->count();

        $view->with('sidebarBadges', [
            'projects' => $projectsCount,
            'tasks'    => $tasksCount,
            'archives' => $archivesCount,
        ]);
    }
}
