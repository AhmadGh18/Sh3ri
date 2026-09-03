<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('favoritable_type', 32); // 'poem' | 'verse'
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_user_target_unique');
            $table->index(['user_id', 'created_at']);
            $table->index(['favoritable_type', 'favoritable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
