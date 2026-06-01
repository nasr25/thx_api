<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppreciationReason;

class AppreciationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'For Help', 'name_ar' => 'للمساعدة', 'sort_order' => 1],
        ];

        foreach ($reasons as $reason) {
            AppreciationReason::firstOrCreate(
                ['name' => $reason['name']],
                array_merge($reason, ['is_active' => true])
            );
        }
    }
}
