<?php

namespace App\Providers;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;


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
        if (config('app.force_https') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        foreach (['accessAdminPanel', 'viewReports', 'manageAreas', 'importUsers', 'deleteUsers'] as $ability) {
            Gate::define($ability, fn (User $user) => $user->canAccessAdminPanel());
        }
        Route::aliasMiddleware('admin', AdminMiddleware::class);
        Livewire::addPersistentMiddleware([
            AdminMiddleware::class,
            ForcePasswordChange::class,
        ]);

    }
}
