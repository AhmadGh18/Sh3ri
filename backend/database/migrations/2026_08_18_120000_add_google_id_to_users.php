<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google's unique `sub` claim, stored per user so subsequent Google sign-ins
 * on the same account bind to the same row even if the user's email changes
 * on Google's side. Nullable because password-only accounts don't have one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 32)->nullable()->unique()->after('email');
            $table->string('avatar_url', 512)->nullable()->after('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url']);
        });
    }
};
