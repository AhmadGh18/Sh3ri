<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('poem_id')->constrained('poems')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('hemistich_a');            // الصدر - required
            $table->text('hemistich_b')->nullable(); // العجز - nullable for free verse
            $table->timestamps();

            $table->unique(['poem_id', 'position']);
        });

        DB::statement("ALTER TABLE verses ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");

        // Full text of the verse for display + search purposes.
        DB::statement(<<<'SQL'
ALTER TABLE verses ADD COLUMN full_text text
GENERATED ALWAYS AS (
    CASE
        WHEN hemistich_b IS NULL OR hemistich_b = '' THEN hemistich_a
        ELSE hemistich_a || ' … ' || hemistich_b
    END
) STORED
SQL);

        DB::statement("ALTER TABLE verses ADD COLUMN full_text_normalized text GENERATED ALWAYS AS (normalize_arabic(hemistich_a || ' ' || coalesce(hemistich_b, ''))) STORED");

        DB::statement(<<<'SQL'
ALTER TABLE verses ADD COLUMN search_tsv tsvector
GENERATED ALWAYS AS (
    to_tsvector('arabic_simple', normalize_arabic(hemistich_a || ' ' || coalesce(hemistich_b, '')))
) STORED
SQL);

        DB::statement('CREATE INDEX verses_search_tsv_idx ON verses USING GIN (search_tsv)');
        DB::statement('CREATE INDEX verses_full_text_trgm_idx ON verses USING GIN (full_text_normalized gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
