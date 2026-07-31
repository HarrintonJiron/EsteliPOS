<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }
        });

        // Generar slugs para roles existentes
        DB::statement('UPDATE roles SET slug = LOWER(name) WHERE slug IS NULL');
        DB::statement('UPDATE roles SET slug = REPLACE(slug, " ", "_") WHERE slug IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('roles', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
