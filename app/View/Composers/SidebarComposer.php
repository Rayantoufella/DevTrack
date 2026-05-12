<?php

namespace App\View\Composers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        if ($view->offsetExists('sidebarBadges')) {
            return;
        }

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $view->with('sidebarBadges', [
            'projects' => $user->projects()->count(),
            'tasks'    => Task::where('user_id', $user->id)->where('status', '!=', 'done')->count(),
            'archives' => $user->projects()->wherePivot('role', 'lead')->onlyTrashed()->count(),
        ]);
    }
}
