<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            SettingSeeder::class,
            RolePermissionSeeder::class,
            AppreciationReasonSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
