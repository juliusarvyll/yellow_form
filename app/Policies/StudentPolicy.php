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
        return true; // Allow all users to view the list of students
    }

    public function view(User $user, Student $student): bool
    {
        return true; // Allow all users to view student details
    }

    // ...other policy methods...
}
