<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function getAll(): array
    {
        return Setting::getAllSettings();
    }

    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    public function update(array $data): array
    {
        $typeMap = [
            'primary_color'               => 'string',
            'secondary_color'             => 'string',
            'accent_color'                => 'string',
            'platform_name_en'            => 'string',
            'platform_name_ar'            => 'string',
            'monthly_appreciation_limit'  => 'integer',
            'max_daily_appreciations'     => 'integer',
            'max_same_receiver_per_month' => 'integer',
            'allow_self_appreciation'     => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'push_notifications_enabled'  => 'boolean',
            'default_language'            => 'string',
            'maintenance_mode'            => 'boolean',
        ];

        $groupMap = [
            'primary_color'               => 'theme',
            'secondary_color'             => 'theme',
            'accent_color'                => 'theme',
            'platform_name_en'            => 'platform',
            'platform_name_ar'            => 'platform',
            'logo_path'                   => 'platform',
            'monthly_appreciation_limit'  => 'rules',
            'max_daily_appreciations'     => 'rules',
            'max_same_receiver_per_month' => 'rules',
            'allow_self_appreciation'     => 'rules',
            'email_notifications_enabled' => 'notifications',
            'push_notifications_enabled'  => 'notifications',
            'default_language'            => 'platform',
            'maintenance_mode'            => 'platform',
        ];

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $typeMap)) {
                continue;
            }

            Setting::setValue(
                $key,
                $value,
                $typeMap[$key] ?? 'string',
                $groupMap[$key] ?? 'general'
            );
        }

        ActivityLog::log('update_settings', 'Platform settings updated', ['keys' => array_keys($data)]);

        return Setting::getAllSettings();
    }

    public function uploadLogo(UploadedFile $file): string
    {
        // Delete old logo
        $oldLogo = Setting::getValue('logo_path');
        if ($oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $file->store('logos', 'public');

        Setting::setValue('logo_path', $path, 'string', 'platform');

        ActivityLog::log('upload_logo', 'Platform logo updated');

        return Storage::disk('public')->url($path);
    }
}
