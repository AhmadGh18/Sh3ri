<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 32);   // poem | poet | correction | metadata
            $table->string('target_type', 32)->nullable(); // 'poem' | 'poet' | 'verse' | null (new content)
            $table->unsignedBigInteger('target_id')->nullable();

            $table->jsonb('payload');            // proposed content
            $table->jsonb('original_snapshot')->nullable();

            $table->string('status', 24)->default('pending'); // pending | approved | rejected | changes_requested
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['target_type', 'target_id']);
        });

        DB::statement("ALTER TABLE submissions ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");

        Schema::create('submission_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('diff')->nullable();
            $table->jsonb('snapshot');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_revisions');
        Schema::dropIfExists('submissions');
    }
};
