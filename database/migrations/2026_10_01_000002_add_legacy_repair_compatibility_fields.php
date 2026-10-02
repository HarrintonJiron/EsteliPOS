<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->time('estimated_time')->nullable();
            $table->boolean('include_warranty_policy')->default(false);
            $table->unsignedSmallInteger('warranty_days')->nullable();
            $table->text('warranty_policy')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->dropColumn(['estimated_time', 'include_warranty_policy', 'warranty_days', 'warranty_policy']);
        });
    }
};
