<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Community features on user-authored poems:
 *  - Upvotes (one per user per poem, deduped by unique constraint)
 *  - Comments (soft-deletable, moderator can also purge)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_poem_upvotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_poem_id')->constrained('user_poems')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_poem_id', 'user_id'], 'user_poem_upvotes_unique');
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_poem_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_poem_id')->constrained('user_poems')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_poem_id', 'created_at']);
        });

        DB::statement("ALTER TABLE user_poem_comments ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_poem_comments');
        Schema::dropIfExists('user_poem_upvotes');
    }
};
