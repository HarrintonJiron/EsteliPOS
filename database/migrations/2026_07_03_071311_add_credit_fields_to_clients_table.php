<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('credit_enabled')->default(false)->after('address');
            $table->decimal('credit_limit', 12, 2)->default(0)->after('credit_enabled');
            $table->unsignedSmallInteger('credit_days')->default(30)->after('credit_limit');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['credit_enabled', 'credit_limit', 'credit_days']);
        });
    }
};
