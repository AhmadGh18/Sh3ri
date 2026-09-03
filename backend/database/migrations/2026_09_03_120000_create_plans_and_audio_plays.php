<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reference table of purchasable tiers. Existing `entitlements` rows
        // reference these by `product_code` (matches `plans.code`).
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();             // guest | free | starter | pro
            $t->string('name_ar', 100);
            $t->string('name_en', 100);
            $t->string('tagline_ar', 160)->nullable();
            $t->integer('price_cents')->default(0);       // monthly, 0 = free
            $t->string('currency', 3)->default('USD');
            // Null daily_audio_plays = unlimited. 0 = zero (blocked).
            $t->integer('daily_audio_plays')->nullable();
            $t->boolean('allow_download')->default(false);
            $t->boolean('is_public')->default(true);      // shows on /plans
            $t->integer('sort')->default(0);
            $t->timestamps();
        });

        // One row per verse-audio request that consumed a play. Cheap to
        // insert, and a covering index over (user_id, played_at) makes the
        // "how many plays today" lookup a fast index range scan.
        Schema::create('audio_plays', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->uuid('verse_uuid');
            $t->timestamp('played_at')->useCurrent();
            $t->index(['user_id', 'played_at']);
            $t->index(['verse_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_plays');
        Schema::dropIfExists('plans');
    }
};
