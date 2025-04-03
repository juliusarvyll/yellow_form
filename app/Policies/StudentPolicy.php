<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Check if user has permission to view students
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('view students')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Student $student): bool
    {
        // Admin and Super Admin can view any student
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Deans can only view students in their department
        if ($user->hasRole('Dean') && $user->hasPermissionTo('view students')) {
            return $user->department_id === $student->department_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('create students')) {
            return true;
        }

        return false;
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('edit students')) {
            return $user->department_id === $student->department_id;
        }

        return false;
    }

    public function delete(User $user, Student $student): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Deans can't delete students
        return false;
    }
}
