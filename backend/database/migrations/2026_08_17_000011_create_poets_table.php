<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 191)->unique();

            $table->string('name_ar', 191);
            $table->string('name_en', 191)->nullable();
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();

            $table->foreignId('era_id')->nullable()->constrained('eras')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();

            $table->smallInteger('birth_year')->nullable();
            $table->smallInteger('death_year')->nullable();
            $table->boolean('is_contested')->default(false);

            $table->string('image_url', 512)->nullable();

            $table->string('source', 64)->nullable();
            $table->string('source_external_id', 191)->nullable();
            $table->jsonb('import_meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE poets ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");

        // Generated normalized name used by search and uniqueness checks.
        DB::statement("ALTER TABLE poets ADD COLUMN name_normalized text GENERATED ALWAYS AS (normalize_arabic(name_ar)) STORED");

        // Stored tsvector fed from normalized name + optional bio.
        DB::statement(<<<'SQL'
ALTER TABLE poets ADD COLUMN search_tsv tsvector
GENERATED ALWAYS AS (
    setweight(to_tsvector('arabic_simple', coalesce(normalize_arabic(name_ar), '')), 'A') ||
    setweight(to_tsvector('arabic_simple', coalesce(normalize_arabic(bio_ar), '')), 'C')
) STORED
SQL);

        DB::statement('CREATE INDEX poets_search_tsv_idx ON poets USING GIN (search_tsv)');
        DB::statement('CREATE INDEX poets_name_normalized_trgm_idx ON poets USING GIN (name_normalized gin_trgm_ops)');
        DB::statement('CREATE UNIQUE INDEX poets_source_external_idx ON poets (source, source_external_id) WHERE source IS NOT NULL AND source_external_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('poets');
    }
};
