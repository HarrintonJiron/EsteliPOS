<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->foreignId('equals_unit_id')->nullable()->after('unit_id')->constrained('units')->nullOnDelete();
            $table->decimal('equals_quantity', 16, 6)->nullable()->after('equals_unit_id');
            $table->boolean('use_for_purchase')->default(true)->after('sale_price');
            $table->boolean('use_for_sale')->default(true)->after('use_for_purchase');
            $table->boolean('is_default_purchase_unit')->default(false)->after('use_for_sale');
            $table->boolean('allow_fraction')->default(false)->after('is_default_sale_unit');
            $table->string('barcode', 100)->nullable()->after('allow_fraction');
            $table->unique('barcode');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('unit_factor', 16, 6)->nullable()->after('quantity');
            $table->decimal('base_quantity', 16, 4)->nullable()->after('unit_factor');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['unit_factor', 'base_quantity']);
        });

        Schema::table('product_unit_conversions', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropConstrainedForeignId('equals_unit_id');
            $table->dropColumn([
                'equals_quantity',
                'use_for_purchase',
                'use_for_sale',
                'is_default_purchase_unit',
                'allow_fraction',
                'barcode',
            ]);
        });
    }
};
