<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_poems', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title_ar', 512);
            $table->text('raw_text'); // user's original text; verse split done later on publish
            $table->foreignId('era_id')->nullable()->constrained('eras')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('status', 16)->default('draft');       // draft | published
            $table->string('visibility', 16)->default('private'); // private | public

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['visibility', 'published_at']);
        });

        DB::statement("ALTER TABLE user_poems ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");
        DB::statement("ALTER TABLE user_poems ADD COLUMN title_normalized text GENERATED ALWAYS AS (normalize_arabic(title_ar)) STORED");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_poems');
    }
};
