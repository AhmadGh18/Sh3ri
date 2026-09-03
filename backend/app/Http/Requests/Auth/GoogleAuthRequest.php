<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ID token as returned by GIS in `credential.credential`.
            // JWTs are dot-separated base64url; length usually 800-2000.
            'id_token' => ['required', 'string', 'min:100', 'max:4096', 'regex:/^[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$/'],
            'device_name' => ['required', 'string', 'max:64'],
        ];
    }
}
