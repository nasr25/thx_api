<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public function rules(): array
    {
        return [
            'platform_name_en'            => 'sometimes|string|max:100',
            'platform_name_ar'            => 'sometimes|string|max:100',
            'primary_color'               => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color'             => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'                => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'monthly_appreciation_limit'  => 'sometimes|integer|min:1|max:100',
            'max_daily_appreciations'     => 'sometimes|integer|min:1|max:50',
            'max_same_receiver_per_month' => 'sometimes|integer|min:1|max:20',
            'allow_self_appreciation'     => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
            'push_notifications_enabled'  => 'sometimes|boolean',
            'default_language'            => 'sometimes|in:en,ar',
        ];
    }
}
