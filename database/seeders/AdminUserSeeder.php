<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminDept = Department::where('code', 'IT')->first();

        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email'              => 'admin@company.com',
                'full_name'          => 'System Administrator',
                'full_name_ar'       => 'مدير النظام',
                'password'           => Hash::make('Admin@12345'),
                'department_id'      => $adminDept?->id,
                'job_title'          => 'IT Administrator',
                'job_title_ar'       => 'مدير تقنية المعلومات',
                'preferred_language' => 'en',
                'is_active'          => true,
            ]
        );

        $admin->assignRole('super-admin');

        // Demo employees
        $demoEmployees = [
            ['username' => 'john.doe',    'email' => 'john.doe@company.com',    'full_name' => 'John Doe',    'full_name_ar' => 'جون دو',     'job_title' => 'Software Engineer',  'dept' => 'IT'],
            ['username' => 'jane.smith',  'email' => 'jane.smith@company.com',  'full_name' => 'Jane Smith',  'full_name_ar' => 'جين سميث',   'job_title' => 'HR Manager',         'dept' => 'HR'],
            ['username' => 'ali.hassan',  'email' => 'ali.hassan@company.com',  'full_name' => 'Ali Hassan',  'full_name_ar' => 'علي حسن',    'job_title' => 'Finance Analyst',    'dept' => 'FIN'],
            ['username' => 'sara.ali',    'email' => 'sara.ali@company.com',    'full_name' => 'Sara Ali',    'full_name_ar' => 'سارة علي',   'job_title' => 'Sales Executive',    'dept' => 'SALES'],
            ['username' => 'omar.khalid', 'email' => 'omar.khalid@company.com', 'full_name' => 'Omar Khalid', 'full_name_ar' => 'عمر خالد',   'job_title' => 'Marketing Manager',  'dept' => 'MKT'],
        ];

        foreach ($demoEmployees as $emp) {
            $dept = Department::where('code', $emp['dept'])->first();
            $user = User::firstOrCreate(
                ['username' => $emp['username']],
                [
                    'email'              => $emp['email'],
                    'full_name'          => $emp['full_name'],
                    'full_name_ar'       => $emp['full_name_ar'],
                    'password'           => Hash::make('Employee@12345'),
                    'department_id'      => $dept?->id,
                    'job_title'          => $emp['job_title'],
                    'preferred_language' => 'en',
                    'is_active'          => true,
                ]
            );
            $user->assignRole('employee');
        }
    }
}
