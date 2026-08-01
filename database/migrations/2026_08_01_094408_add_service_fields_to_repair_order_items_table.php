<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repair_order_items', function (Blueprint $table) {
            $table->string('item_type')->default('part')->after('product_id')->comment('part=repuesto, service=servicio');
            $table->string('device_brand')->nullable()->after('description')->comment('Marca para servicios específicos');
            $table->unsignedBigInteger('service_id')->nullable()->after('product_id')->comment('ID del servicio predefinido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repair_order_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'device_brand', 'service_id']);
        });
    }
};
