<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gates per il sistema ACL del gestionale
        Gate::define('admin', function (User $user) {
            return $user->ruolo === 'admin';
        });

        Gate::define('access-mezzi', function (User $user) {
            return $user->canAccessMezzi();
        });

        Gate::define('view-logs', function (User $user) {
            return $user->canViewLogs();
        });

        Gate::define('configure-acl', function (User $user) {
            return $user->canConfigureACL();
        });

        // Gate per permessi specifici per modulo
        Gate::define('permission', function (User $user, $modulo, $azione) {
            return $user->hasPermission($modulo, $azione);
        });
    }
}