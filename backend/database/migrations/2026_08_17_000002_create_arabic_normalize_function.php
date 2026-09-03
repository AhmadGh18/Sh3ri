<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Deterministic Arabic normalizer. Used by generated columns and search.
        // Rules mirror App\Domain\Poetry\Support\ArabicNormalizer so PHP and SQL agree.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION normalize_arabic(input text)
RETURNS text
LANGUAGE plpgsql
IMMUTABLE
PARALLEL SAFE
STRICT
AS $$
DECLARE
    result text;
BEGIN
    result := input;

    -- Strip Arabic diacritics (tashkeel) and Quranic marks
    result := regexp_replace(result, '[ً-ٰٟۖ-ۭ]', '', 'g');

    -- Strip Tatweel (kashida)
    result := replace(result, U&'\0640', '');

    -- Fold Hamza variants -> bare Alif
    result := translate(result, U&'\0623\0625\0622\0671', 'اااا');

    -- Fold Hamza-on-Waw and Hamza-on-Ya
    result := translate(result, U&'\0624\0626', 'وي');

    -- Fold Alif-Maqsura -> Ya
    result := replace(result, U&'\0649', 'ي');

    -- Fold Ta-Marbuta -> Ha (search only; original preserved for display)
    result := replace(result, U&'\0629', U&'\0647');

    -- Arabic-Indic digits -> ASCII digits
    result := translate(result, U&'\0660\0661\0662\0663\0664\0665\0666\0667\0668\0669', '0123456789');
    -- Extended Persian digits
    result := translate(result, U&'\06F0\06F1\06F2\06F3\06F4\06F5\06F6\06F7\06F8\06F9', '0123456789');

    -- Strip zero-width joiners / non-joiners / BOM
    result := regexp_replace(result, '[​-‏‪-‮﻿]', '', 'g');

    -- Collapse whitespace
    result := regexp_replace(result, '\s+', ' ', 'g');
    result := btrim(result);

    RETURN lower(result);
END;
$$;
SQL);

        // Postgres text search config for Arabic: `simple` dictionary against
        // pre-normalized text gives better recall than any morphological
        // stemmer for poetry (where morphology guesses are wrong more often
        // than they help).
        DB::unprepared(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_ts_config WHERE cfgname = 'arabic_simple'
    ) THEN
        CREATE TEXT SEARCH CONFIGURATION arabic_simple (COPY = simple);
    END IF;
END
$$;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TEXT SEARCH CONFIGURATION IF EXISTS arabic_simple');
        DB::statement('DROP FUNCTION IF EXISTS normalize_arabic(text)');
    }
};
