<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
            $table->string('business_name')->nullable()->after('name');
            $table->string('ruc', 30)->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['code', 'business_name', 'ruc']);
        });
    }
};

