<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Support;

/**
 * Arabic text normalizer.
 *
 * Rules mirror the SQL `normalize_arabic()` function so PHP-side validation
 * and Postgres-side generated columns agree on what "normalized" means.
 * Any change here must be reflected in the migration that defines
 * normalize_arabic() and vice versa.
 */
final class ArabicNormalizer
{
    private const TASHKEEL = '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';

    private const HAMZA_TO_ALIF = ['أ', 'إ', 'آ', 'ٱ'];
    private const HAMZA_ON_WAW = 'ؤ';
    private const HAMZA_ON_YA = 'ئ';
    private const ALIF_MAQSURA = 'ى';
    private const TA_MARBUTA = 'ة';
    private const HA = 'ه';
    private const TATWEEL = 'ـ';

    private const ARABIC_INDIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const ASCII_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const ZERO_WIDTH = '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u';

    public function normalize(?string $input): string
    {
        if ($input === null || $input === '') {
            return '';
        }

        // NFC first so composed / decomposed forms match.
        $text = \Normalizer::normalize($input, \Normalizer::FORM_C) ?: $input;

        $text = preg_replace(self::TASHKEEL, '', $text) ?? $text;
        $text = str_replace(self::TATWEEL, '', $text);

        $text = str_replace(self::HAMZA_TO_ALIF, 'ا', $text);
        $text = str_replace(self::HAMZA_ON_WAW, 'و', $text);
        $text = str_replace(self::HAMZA_ON_YA, 'ي', $text);
        $text = str_replace(self::ALIF_MAQSURA, 'ي', $text);
        $text = str_replace(self::TA_MARBUTA, self::HA, $text);

        $text = str_replace(self::ARABIC_INDIC_DIGITS, self::ASCII_DIGITS, $text);
        $text = str_replace(self::PERSIAN_DIGITS, self::ASCII_DIGITS, $text);

        $text = preg_replace(self::ZERO_WIDTH, '', $text) ?? $text;

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return mb_strtolower($text);
    }

    /**
     * Prepare a search query the same way stored text was normalized.
     * Rejects tokens shorter than $minLen (default 2) to avoid indexing noise.
     *
     * @return array{normalized: string, tokens: list<string>}
     */
    public function prepareQuery(string $query, int $minLen = 2): array
    {
        $normalized = $this->normalize($query);
        $tokens = array_values(array_filter(
            preg_split('/\s+/u', $normalized) ?: [],
            static fn (string $t): bool => mb_strlen($t) >= $minLen,
        ));

        return ['normalized' => $normalized, 'tokens' => $tokens];
    }
}
