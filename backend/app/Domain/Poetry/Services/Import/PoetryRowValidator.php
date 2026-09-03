<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

/**
 * Per-row validation for imported poetry.
 * Returns null on success or a machine-readable reason string on failure.
 *
 * Reasons intentionally live in this class as a closed set so
 * ImportReport can aggregate them cleanly for the final report.
 */
final class PoetryRowValidator
{
    public const REASON_MISSING_POET = 'missing_poet_name';
    public const REASON_MISSING_TITLE = 'missing_poem_title';
    public const REASON_MISSING_TEXT = 'missing_poem_text';
    public const REASON_TEXT_TOO_SHORT = 'text_too_short';
    public const REASON_INVALID_ENCODING = 'invalid_encoding';

    private const MIN_TEXT_LENGTH = 8;

    /** @param  array<string, string|null>  $projected */
    public function validate(array $projected): ?string
    {
        $poet = $projected['poet_name'] ?? null;
        $title = $projected['poem_title'] ?? null;
        $text = $projected['poem_text'] ?? null;

        if ($poet === null || $poet === '') {
            return self::REASON_MISSING_POET;
        }
        if ($title === null || $title === '') {
            return self::REASON_MISSING_TITLE;
        }
        if ($text === null || $text === '') {
            return self::REASON_MISSING_TEXT;
        }
        if (mb_strlen($text) < self::MIN_TEXT_LENGTH) {
            return self::REASON_TEXT_TOO_SHORT;
        }
        // Reject rows with invalid UTF-8 (usually double-encoded scrapes).
        foreach ([$poet, $title, $text] as $s) {
            if ($s !== null && ! mb_check_encoding($s, 'UTF-8')) {
                return self::REASON_INVALID_ENCODING;
            }
        }

        return null;
    }
}
