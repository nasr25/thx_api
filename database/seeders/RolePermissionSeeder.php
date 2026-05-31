<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Appreciations
            'send-appreciation',
            'view-appreciations',
            'delete-appreciation',
            'manage-appreciations',

            // Users
            'view-employees',
            'manage-users',
            'impersonate-users',

            // Admin
            'access-admin-panel',
            'manage-settings',
            'view-analytics',
            'view-activity-logs',
            'export-reports',
            'manage-departments',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employeeRole->syncPermissions([
            'send-appreciation',
            'view-appreciations',
            'view-employees',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($permissions);
    }
}
