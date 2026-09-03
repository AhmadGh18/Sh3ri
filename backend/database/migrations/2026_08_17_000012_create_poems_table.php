<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poems', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 191)->unique();

            $table->foreignId('poet_id')->constrained('poets')->cascadeOnDelete();
            $table->foreignId('era_id')->nullable()->constrained('eras')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('meter_id')->nullable()->constrained('meters')->nullOnDelete();

            $table->string('title_ar', 512);
            $table->string('title_en', 512)->nullable();
            $table->string('language', 8)->default('ar');
            $table->unsignedInteger('verse_count')->default(0);

            $table->string('status', 16)->default('published'); // published | hidden | quarantined
            $table->timestamp('published_at')->nullable();

            $table->text('raw_source_text')->nullable(); // exact original text pre-split
            $table->string('source', 64)->nullable();
            $table->string('source_external_id', 191)->nullable();
            $table->jsonb('import_meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE poems ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");

        DB::statement("ALTER TABLE poems ADD COLUMN title_normalized text GENERATED ALWAYS AS (normalize_arabic(title_ar)) STORED");

        DB::statement(<<<'SQL'
ALTER TABLE poems ADD COLUMN search_tsv tsvector
GENERATED ALWAYS AS (
    setweight(to_tsvector('arabic_simple', coalesce(normalize_arabic(title_ar), '')), 'A')
) STORED
SQL);

        DB::statement('CREATE INDEX poems_search_tsv_idx ON poems USING GIN (search_tsv)');
        DB::statement('CREATE INDEX poems_title_normalized_trgm_idx ON poems USING GIN (title_normalized gin_trgm_ops)');
        DB::statement('CREATE INDEX poems_poet_status_idx ON poems (poet_id, status)');
        DB::statement('CREATE INDEX poems_era_category_idx ON poems (era_id, category_id)');
        DB::statement('CREATE INDEX poems_status_published_at_idx ON poems (status, published_at DESC NULLS LAST)');
        DB::statement('CREATE UNIQUE INDEX poems_source_external_idx ON poems (source, source_external_id) WHERE source IS NOT NULL AND source_external_id IS NOT NULL');

        Schema::create('poem_topic', function (Blueprint $table) {
            $table->foreignId('poem_id')->constrained('poems')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->primary(['poem_id', 'topic_id']);
            $table->index(['topic_id', 'poem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poem_topic');
        Schema::dropIfExists('poems');
    }
};
