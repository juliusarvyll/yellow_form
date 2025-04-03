<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('view courses')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('view courses')) {
            // Deans can only view courses in their department
            return $user->department_id === $course->department_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin']) && $user->hasPermissionTo('create courses')) {
            return true;
        }

        return false;
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin']) && $user->hasPermissionTo('edit courses')) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin']) && $user->hasPermissionTo('delete courses')) {
            return true;
        }

        return false;
    }
}
