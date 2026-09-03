<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Slug generation for Arabic-titled entities.
 *
 * Strategy: Latin transliteration is inconsistent for Arabic, so we
 * normalize + take the first N Arabic tokens, then append a short
 * base36 hash of the full normalized text. That produces stable,
 * URL-safe, unique slugs without cross-language ambiguity.
 *
 * Examples:
 *   "أبو الطيب المتنبي"      → "abw-altyb-almtnby-abcd12"  (translit fallback)
 *   "على قدر أهل العزم"       → "ali-qadr-ahl-alazm-a1b2c3"
 */
final class SlugGenerator
{
    /** Simple char-by-char Arabic→Latin transliteration for slugs only. */
    private const TRANSLIT = [
        'ا' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'ح' => 'h',
        'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z', 'س' => 's',
        'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a',
        'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm',
        'ن' => 'n', 'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ة' => 'h',
        'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ؤ' => 'w', 'ئ' => 'y', 'ء' => '',
    ];

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function generate(string $arabicText, string $table, string $column = 'slug'): string
    {
        $normalized = $this->normalizer->normalize($arabicText);
        $translit = strtr($normalized, self::TRANSLIT);
        $base = Str::slug($translit, '-') ?: 'poem';

        // Cap length to keep URLs sane; add hash suffix to disambiguate.
        $base = Str::limit($base, 60, '');
        $hash = substr(md5($normalized), 0, 6);
        $slug = trim($base, '-') . '-' . $hash;

        // Guarantee uniqueness even under hash collisions.
        $candidate = $slug;
        $i = 1;
        while ($this->exists($table, $column, $candidate)) {
            $candidate = $slug . '-' . $i++;
            if ($i > 50) {
                $candidate = $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
                break;
            }
        }

        return $candidate;
    }

    private function exists(string $table, string $column, string $value): bool
    {
        return DB::table($table)->where($column, $value)->exists();
    }
}
