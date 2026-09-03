<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name_ar', 128);
            $table->string('name_en', 128)->nullable();
            $table->char('iso_code', 2)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('eras', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name_ar', 128);
            $table->string('name_en', 128)->nullable();
            $table->smallInteger('start_year')->nullable();
            $table->smallInteger('end_year')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('display_order');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name_ar', 128);
            $table->string('name_en', 128)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'display_order']);
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name_ar', 128);
            $table->string('name_en', 128)->nullable();
            $table->string('color', 16)->nullable();
            $table->timestamps();
        });

        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name_ar', 128); // e.g. الطويل
            $table->string('name_en', 128)->nullable();
            $table->string('pattern', 255)->nullable(); // classical تفعيلة
            $table->string('family', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meters');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('eras');
        Schema::dropIfExists('countries');
    }
};
