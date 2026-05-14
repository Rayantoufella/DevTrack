<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
View::composer([
            'dashboard', 'projects.index', 'projects.show', 'projects.archives',
            'projects.create', 'projects.edit',
            'tasks.create', 'tasks.edit',
        ], SidebarComposer::class);
    }
}
