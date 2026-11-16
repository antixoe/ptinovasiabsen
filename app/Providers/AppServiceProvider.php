<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user) {
            return $user->isSuperadmin() ? true : null;
        });

        foreach (array_keys(Role::PERMISSIONS) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        Gate::define('manage-agenda-for', function (User $user, User $owner) {
            return $user->id === $owner->id
                || $user->canManageAllAgendas()
                || ($user->isMentor() && $owner->mentor_id === $user->id);
        });

        Gate::define('review-report-for', function (User $user, User $owner) {
            return $user->canReviewReports()
                && ($user->canViewAllReports() || $owner->mentor_id === $user->id);
        });
    }
}
