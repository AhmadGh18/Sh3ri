<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reports carry PII (complaint text, other-user references). Bind admin
     * routes to a UUID instead of the incrementing id so a compromised
     * moderator token can't just iterate 1..N to enumerate the whole queue.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::statement("ALTER TABLE reports ALTER COLUMN uuid SET DEFAULT uuid_generate_v4()");
        DB::statement("UPDATE reports SET uuid = uuid_generate_v4() WHERE uuid IS NULL");
        DB::statement('ALTER TABLE reports ALTER COLUMN uuid SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX reports_uuid_unique ON reports (uuid)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reports_uuid_unique');
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
