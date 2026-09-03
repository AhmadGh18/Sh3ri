<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
        DB::statement('DROP EXTENSION IF EXISTS citext');
        DB::statement('DROP EXTENSION IF EXISTS unaccent');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
