<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'email' => ['sometimes', 'string', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'locale' => ['sometimes', 'in:ar,en'],
        ];
    }
}
