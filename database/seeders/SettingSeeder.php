<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Platform identity
            ['key' => 'platform_name_en',            'value' => 'Appreciation Platform', 'type' => 'string',  'group' => 'platform'],
            ['key' => 'platform_name_ar',            'value' => 'منصة التقدير',          'type' => 'string',  'group' => 'platform'],
            ['key' => 'logo_path',                   'value' => null,                    'type' => 'string',  'group' => 'platform'],
            ['key' => 'primary_color',               'value' => '#004137',               'type' => 'string',  'group' => 'theme'],
            ['key' => 'secondary_color',             'value' => '#00281E',               'type' => 'string',  'group' => 'theme'],
            ['key' => 'accent_color',                'value' => '#000A0F',               'type' => 'string',  'group' => 'theme'],

            // Appreciation rules
            ['key' => 'monthly_appreciation_limit',  'value' => '10',                    'type' => 'integer', 'group' => 'rules'],
            ['key' => 'allow_self_appreciation',     'value' => '0',                     'type' => 'boolean', 'group' => 'rules'],
            ['key' => 'max_daily_appreciations',     'value' => '5',                     'type' => 'integer', 'group' => 'rules'],
            ['key' => 'max_same_receiver_per_month', 'value' => '1',                     'type' => 'integer', 'group' => 'rules'],

            // Notifications
            ['key' => 'email_notifications_enabled', 'value' => '1',                     'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'push_notifications_enabled',  'value' => '1',                     'type' => 'boolean', 'group' => 'notifications'],

            // Platform
            ['key' => 'default_language',            'value' => 'en',                    'type' => 'string',  'group' => 'platform'],
            ['key' => 'maintenance_mode',            'value' => '0',                     'type' => 'boolean', 'group' => 'platform'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
