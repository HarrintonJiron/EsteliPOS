<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'pin_hash')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('pin_hash')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'pin_hash')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('pin_hash');
        });
    }
};
