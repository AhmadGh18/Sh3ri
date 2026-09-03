<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 191);
            $table->string('email', 191); // altered to CITEXT below
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 8)->default('ar');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        // Case-insensitive email via CITEXT + unique constraint, uuid defaulting on DB side.
        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE CITEXT USING (email::citext)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email)');
        DB::statement("ALTER TABLE users ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        DB::statement('ALTER TABLE password_reset_tokens ALTER COLUMN email TYPE CITEXT USING (email::citext)');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
