<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-permits', fn (User $user) => $user->canManagePermits());
        Gate::define('delete-permits', fn (User $user) => $user->isAdmin());
        Gate::define('manage-categories', fn (User $user) => $user->isAdmin());
        Gate::define('manage-contacts', fn (User $user) => $user->canManageContacts());
        Gate::define('delete-contacts', fn (User $user) => $user->isAdmin());
        Gate::define('manage-reminders', fn (User $user) => $user->isAdmin());
        Gate::define('view-deliveries', fn (User $user) => in_array($user->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Secretary], true));
        Gate::define('manage-app', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('view-audit', fn (User $user) => $user->isAdmin());

        RateLimiter::for('waha-send', fn () => Limit::perMinute(20)->by('waha'));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
