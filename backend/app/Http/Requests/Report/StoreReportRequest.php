<?php

declare(strict_types=1);

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', Rule::in(['poem', 'verse', 'poet', 'user_poem', 'submission'])],
            'reportable_id' => ['required', 'integer', 'min:1', $this->existsInTargetTable()],
            'reason' => ['required', Rule::in([
                'inaccurate_attribution', 'misattributed_verse', 'wrong_text',
                'wrong_metadata', 'copyright', 'spam', 'offensive', 'other',
            ])],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Assert that (reportable_type, reportable_id) points at a row that
     * actually exists — otherwise a user could enumerate ids ("does poem
     * 99999 exist?") or flood moderators with phantom rows.
     */
    private function existsInTargetTable(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $type = $this->input('reportable_type');
            $table = match ($type) {
                'poem'       => 'poems',
                'verse'      => 'verses',
                'poet'       => 'poets',
                'user_poem'  => 'user_poems',
                'submission' => 'submissions',
                default      => null,
            };
            if ($table === null) return; // caught by the `in:` rule above.

            $exists = \Illuminate\Support\Facades\DB::table($table)
                ->where('id', $value)
                ->exists();
            if (! $exists) {
                $fail('The selected target does not exist.');
            }
        };
    }
}
