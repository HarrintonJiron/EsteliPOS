<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->boolean('include_warranty_policy')->default(false)->after('repair_notes');
            $table->unsignedSmallInteger('warranty_days')->nullable()->after('include_warranty_policy');
            $table->text('warranty_policy')->nullable()->after('warranty_days');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropColumn(['include_warranty_policy', 'warranty_days', 'warranty_policy']);
        });
    }
};
