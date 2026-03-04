<?php

namespace App\Providers;

use App\Models\Movie;
use App\Models\User;
use App\Policies\MoviePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Movie::class => MoviePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('access-admin-panel', fn (User $user): bool => $user->canAccessAdminPanel());
        Gate::define('manage-users', fn (User $user): bool => $user->canManageUsers());
        Gate::define('manage-content', fn (User $user): bool => $user->canManageContent());
        Gate::define('delete-content', fn (User $user): bool => $user->canDeleteContent());
        Gate::define('view-reports', fn (User $user): bool => $user->canViewReports());
    }
}
