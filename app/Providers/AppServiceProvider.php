<?php

namespace App\Providers;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForcePasswordChange;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        Gate::define('viewReports', fn (User $user) => in_array($user->role, ['admin', 'rh', 'hr_manager'], true));
        Route::aliasMiddleware('admin', AdminMiddleware::class);
        Livewire::addPersistentMiddleware([
            AdminMiddleware::class,
            ForcePasswordChange::class,
        ]);

    }
}
