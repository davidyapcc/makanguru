<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;

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
        // Define gate for admin access to scraper and restaurant management
        Gate::define('access-admin-features', function (?User $user = null) {
            // Check if the session has the admin access key
            $adminKey = config('app.admin_access_key');

            // If no admin key is set in config, allow access (development mode)
            if (empty($adminKey)) {
                return true;
            }

            // Check if session has valid admin key
            return session('admin_authenticated') === true;
        });
    }
}
