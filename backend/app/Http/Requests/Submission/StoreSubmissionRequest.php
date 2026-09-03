<?php

declare(strict_types=1);

namespace App\Http\Requests\Submission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['poem', 'poet', 'correction', 'metadata'])],
            'target_type' => ['nullable', Rule::in(['poem', 'poet', 'verse'])],
            'target_id' => ['nullable', 'integer', 'min:1'],
            // Cap the outer payload so a user can't jam a 5-MB blob into jsonb.
            'payload' => ['required', 'array', 'max:32'],
            'payload.title_ar' => ['sometimes', 'string', 'max:512'],
            'payload.text' => ['sometimes', 'string', 'max:20000'],
            'payload.name_ar' => ['sometimes', 'string', 'max:191'],
            'payload.era_slug' => ['sometimes', 'string', 'exists:eras,slug'],
            'payload.category_slug' => ['sometimes', 'string', 'exists:categories,slug'],
            'payload.country_slug' => ['sometimes', 'string', 'exists:countries,slug'],
            'payload.note' => ['sometimes', 'string', 'max:1000'],
        ];
    }
}
