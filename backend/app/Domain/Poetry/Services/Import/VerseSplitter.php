<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

/**
 * Splits a raw Arabic poem_text field into ordered verses, each with
 * a صدر (hemistich A) and نص عجز (hemistich B).
 *
 * Handles the several conventions found in Aldiwan-scraped corpora:
 *   - Verses separated by \n or \r\n
 *   - Hemistiches separated by " * ", " # ", " -- ", multiple spaces,
 *     or a tab character
 *   - Free-verse (شعر تفعيلة) where a whole line has no hemistich break;
 *     in that case hemistich_b is null.
 *
 * Also tolerates poems that arrive as a single line with hemistich
 * separators repeated (whole poem in one string).
 */
final class VerseSplitter
{
    /** @var list<string> */
    private const VERSE_SEPARATORS = ["\r\n", "\n", "\r"];

    /**
     * Regex used to split a single verse line into two hemistiches.
     * We look for common Aldiwan patterns AND long runs of whitespace.
     */
    private const HEMISTICH_SPLIT = '/(?:\s*\*\s*|\s*#\s*|\s*--\s*|\t+| {3,})/u';

    /**
     * @return list<array{hemistich_a: string, hemistich_b: string|null}>
     */
    public function split(string $rawText): array
    {
        $rawText = str_replace(["\u{FEFF}"], '', $rawText); // strip BOM

        $lines = preg_split('/(?:\r\n|\n|\r)+/u', $rawText) ?: [];
        $lines = array_values(array_filter(
            array_map('trim', $lines),
            static fn (string $line): bool => $line !== '',
        ));

        // Fallback: one giant line with `*` between hemistiches AND between verses.
        if (count($lines) === 1 && substr_count($lines[0], '*') >= 3) {
            $lines = $this->splitOneLinePoem($lines[0]);
        }

        // Detect whether any line contains an inline hemistich separator.
        $anyInlineSplit = false;
        foreach ($lines as $line) {
            if (preg_match(self::HEMISTICH_SPLIT, $line) === 1) {
                $anyInlineSplit = true;
                break;
            }
        }

        // ahmedabelal shape: each hemistich lives on its own line, no inline
        // separator. Classical Arabic poetry is bipartite, so if lines are
        // even in count and no inline separator was seen, pair (A, B).
        if (! $anyInlineSplit && count($lines) >= 2 && count($lines) % 2 === 0) {
            return $this->pairLines($lines);
        }

        $verses = [];
        foreach ($lines as $line) {
            $parts = preg_split(self::HEMISTICH_SPLIT, $line, 2) ?: [$line];
            $a = trim($parts[0]);
            $b = isset($parts[1]) ? trim($parts[1]) : null;

            if ($a === '') {
                continue;
            }
            $verses[] = [
                'hemistich_a' => $a,
                'hemistich_b' => ($b === null || $b === '') ? null : $b,
            ];
        }

        return $verses;
    }

    /**
     * Pair consecutive lines into (A, B) hemistiches. Used when the source
     * stores each hemistich on its own line without a separator.
     *
     * @param  list<string>  $lines
     * @return list<array{hemistich_a: string, hemistich_b: string|null}>
     */
    private function pairLines(array $lines): array
    {
        $verses = [];
        for ($i = 0, $n = count($lines); $i < $n; $i += 2) {
            $verses[] = [
                'hemistich_a' => $lines[$i],
                'hemistich_b' => $lines[$i + 1] ?? null,
            ];
        }

        return $verses;
    }

    /**
     * Heuristic: some rows arrive as "sadr * ajz * sadr * ajz * …".
     * We pair adjacent hemistich chunks into verses.
     *
     * @return list<string>
     */
    private function splitOneLinePoem(string $line): array
    {
        $chunks = array_values(array_filter(
            array_map('trim', explode('*', $line)),
            static fn (string $c): bool => $c !== '',
        ));

        $out = [];
        for ($i = 0, $n = count($chunks); $i < $n; $i += 2) {
            $out[] = isset($chunks[$i + 1])
                ? $chunks[$i] . ' * ' . $chunks[$i + 1]
                : $chunks[$i];
        }

        return $out;
    }
}
