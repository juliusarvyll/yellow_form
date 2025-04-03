<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Yellow Form permissions
            'view yellow forms',
            'create yellow forms',
            'edit yellow forms',
            'delete yellow forms',

            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Department management
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Course management
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',

            // Violation management
            'view violations',
            'create violations',
            'edit violations',
            'delete violations',

            // Student management
            'view students',
            'create students',
            'edit students',
            'delete students',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $roles = [
            'Super Admin' => $permissions,
            'Dean' => [
                'view yellow forms',
                'create yellow forms',
                'edit yellow forms',
                'view violations',
                'create violations',
                'edit violations',
                'view students',
                'view courses',
            ],
            'Admin' => [
                'view yellow forms',
                'view users',
                'create users',
                'edit users',
                'view departments',
                'create departments',
                'edit departments',
                'view courses',
                'create courses',
                'edit courses',
                'view students',
                'create students',
                'edit students',
            ],
            'Student' => [
                'view yellow forms',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // Assign Super Admin role to user with ID 1 (if exists)
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->assignRole('Super Admin');
        }
    }
}
