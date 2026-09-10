<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
            "fcm_token" => ['sometimes', 'string'],
            'force' => ['sometimes', 'boolean'],

        ];
    }
}
