<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Prevent deletion of core permissions
        $corePermissions = [
            'view yellow forms', 'create yellow forms', 'edit yellow forms', 'delete yellow forms',
            'view users', 'create users', 'edit users', 'delete users',
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view courses', 'create courses', 'edit courses', 'delete courses',
            'view violations', 'create violations', 'edit violations', 'delete violations',
            'view students', 'create students', 'edit students', 'delete students',
        ];

        if (in_array($permission->name, $corePermissions)) {
            return false;
        }

        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        // Prevent force deletion of core permissions
        $corePermissions = [
            'view yellow forms', 'create yellow forms', 'edit yellow forms', 'delete yellow forms',
            'view users', 'create users', 'edit users', 'delete users',
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view courses', 'create courses', 'edit courses', 'delete courses',
            'view violations', 'create violations', 'edit violations', 'delete violations',
            'view students', 'create students', 'edit students', 'delete students',
        ];

        if (in_array($permission->name, $corePermissions)) {
            return false;
        }

        return $user->hasRole('Super Admin');
    }
}
