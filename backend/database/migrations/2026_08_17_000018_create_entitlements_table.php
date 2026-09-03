<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entitlements decouple "does user have feature X" from any payment provider.
        // Right now the EntitlementsService treats every authenticated user as
        // entitled to every product (see project-entitlements-open-access memory);
        // this table is the seat that Apple/Google/Stripe adapters will write into later.
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('product_code', 64);
            $table->string('source', 24);   // apple | google | stripe | manual | promo | open
            $table->string('status', 16);   // active | expired | refunded | paused
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('original_transaction_id', 191)->nullable();
            $table->jsonb('raw_receipt')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_code', 'original_transaction_id'], 'entitlements_unique_transaction');
            $table->index(['user_id', 'status']);
            $table->index(['product_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
