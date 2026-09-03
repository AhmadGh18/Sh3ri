<?php

declare(strict_types=1);

namespace App\Http\Requests\UserPoem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPoemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route param matches the segment name in api.php: `{userPoem:uuid}`.
        // The controller ALSO calls $this->authorize('update', $userPoem) as
        // defense in depth, so this file is not the only guard.
        return $this->user()?->can('update', $this->route('userPoem')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['sometimes', 'string', 'max:512'],
            'raw_text' => ['sometimes', 'string', 'min:8'],
            'era_id' => ['sometimes', 'nullable', 'integer', 'exists:eras,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'visibility' => ['sometimes', 'in:private,public'],
        ];
    }
}
