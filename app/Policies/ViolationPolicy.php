<?php

namespace App\Policies;

use App\Models\Violation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ViolationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view violations');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Violation $violation): bool
    {
        return $user->hasPermissionTo('view violations');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create violations');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Violation $violation): bool
    {
        return $user->hasPermissionTo('edit violations');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Violation $violation): bool
    {
        // Only Super Admin and Admin can delete violations
        return $user->hasRole(['Super Admin', 'Admin']) &&
               $user->hasPermissionTo('delete violations');
    }
}
