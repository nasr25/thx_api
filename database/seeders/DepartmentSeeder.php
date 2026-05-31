<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'name_ar' => 'تكنولوجيا المعلومات', 'code' => 'IT'],
            ['name' => 'Human Resources',        'name_ar' => 'الموارد البشرية',     'code' => 'HR'],
            ['name' => 'Finance',                'name_ar' => 'المالية',              'code' => 'FIN'],
            ['name' => 'Operations',             'name_ar' => 'العمليات',             'code' => 'OPS'],
            ['name' => 'Sales',                  'name_ar' => 'المبيعات',             'code' => 'SALES'],
            ['name' => 'Marketing',              'name_ar' => 'التسويق',              'code' => 'MKT'],
            ['name' => 'Customer Service',       'name_ar' => 'خدمة العملاء',         'code' => 'CS'],
            ['name' => 'Legal',                  'name_ar' => 'الشؤون القانونية',     'code' => 'LEGAL'],
            ['name' => 'Administration',         'name_ar' => 'الإدارة',              'code' => 'ADMIN'],
            ['name' => 'Research & Development', 'name_ar' => 'البحث والتطوير',       'code' => 'RD'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], array_merge($dept, ['is_active' => true]));
        }
    }
}
