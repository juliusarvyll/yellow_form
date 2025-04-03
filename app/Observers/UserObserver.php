<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // This is already handled through the form
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Automatically assign the Dean role if a department is set
        // and the user doesn't already have the Dean role
        if ($user->isDirty('department_id') && $user->department_id && !$user->hasRole('Dean')) {
            $user->assignRole('Dean');
        }

        // If department is removed, remove the Dean role
        if ($user->isDirty('department_id') && $user->department_id === null && $user->hasRole('Dean')) {
            $user->removeRole('Dean');
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
