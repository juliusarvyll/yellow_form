<?php

namespace App\Policies;

use App\Models\YellowForm;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class YellowFormPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Dean')) {
            // Deans can view the index page
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, YellowForm $yellowForm): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Dean')) {
            // Deans can only view forms from their department
            return $user->department_id === $yellowForm->department_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Dean')) {
            // Deans can create forms, but they will be restricted to their department
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, YellowForm $yellowForm): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->hasRole('Dean')) {
            // Deans can only update forms from their department
            return $user->department_id === $yellowForm->department_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, YellowForm $yellowForm): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return true;
        }

        // Deans shouldn't be able to delete forms
        return false;
    }
}
