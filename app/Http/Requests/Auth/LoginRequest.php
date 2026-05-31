<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:100',
            'password' => 'required|string|min:6|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('validation.required', ['attribute' => __('validation.attributes.username')]),
            'password.required' => __('validation.required', ['attribute' => __('validation.attributes.password')]),
        ];
    }
}
