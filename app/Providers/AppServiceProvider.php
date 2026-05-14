<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'dashboard', 'projects.index', 'projects.show', 'projects.archives',
            'projects.create', 'projects.edit',
            'tasks.create', 'tasks.edit',
        ], SidebarComposer::class);
    }
}
