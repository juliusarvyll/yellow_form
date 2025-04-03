<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean') && $user->hasPermissionTo('view departments')) {
            return true;
        }

        return false;
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($user->hasRole('Dean')) {
            // Deans can only view their own department
            return $user->department_id === $department->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']) &&
               $user->hasPermissionTo('create departments');
    }

    public function update(User $user, Department $department): bool
    {
        // Only Super Admin and Admin can update departments
        return $user->hasRole(['Super Admin', 'Admin']) &&
               $user->hasPermissionTo('edit departments');
    }

    public function delete(User $user, Department $department): bool
    {
        // Only Super Admin and Admin can delete departments
        return $user->hasRole(['Super Admin', 'Admin']) &&
               $user->hasPermissionTo('delete departments');
    }
}
