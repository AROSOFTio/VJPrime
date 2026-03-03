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
    }
}
