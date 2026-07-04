<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // Super-admins bypass all Gate/Policy checks without needing any role or permission assigned.
        Gate::before(function ($user) {
            if ($user->is_super_admin) {
                return true;
            }
        });
    }
}
