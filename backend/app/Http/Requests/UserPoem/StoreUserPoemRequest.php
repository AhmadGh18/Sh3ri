<?php

declare(strict_types=1);

namespace App\Http\Requests\UserPoem;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserPoemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:512'],
            'raw_text' => ['required', 'string', 'min:8'],
            'era_id' => ['nullable', 'integer', 'exists:eras,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'visibility' => ['nullable', 'in:private,public'],
        ];
    }
}
