<?php

namespace App\Http\Requests\Appreciation;

use Illuminate\Foundation\Http\FormRequest;

class SendAppreciationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => 'required|integer|exists:users,id',
            'reason_id'   => 'required|integer|exists:appreciation_reasons,id',
            'message'     => 'nullable|string|max:1000',
            'is_public'   => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.required' => __('validation.required', ['attribute' => 'receiver']),
            'receiver_id.exists'   => __('messages.user_not_found'),
            'reason_id.required'   => __('messages.reason_required'),
            'reason_id.exists'     => __('messages.reason_not_found'),
            'message.max'          => __('validation.max.string', ['attribute' => 'message', 'max' => 1000]),
        ];
    }
}
