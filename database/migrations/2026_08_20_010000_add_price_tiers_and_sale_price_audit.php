<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the old composite unique index to enforce the price-list FK.
        // Give that FK a dedicated index before replacing the unique constraint.
        Schema::table('price_list_items', function (Blueprint $table) {
            $table->index('price_list_id', 'price_list_items_price_list_id_index');
        });
        Schema::table('price_list_items', function (Blueprint $table) {
            $table->dropUnique('price_list_product_unit_unique');
            $table->unique(['price_list_id', 'product_id', 'unit_id', 'min_quantity'], 'price_list_product_unit_quantity_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('warehouse_id')->constrained('price_lists')->nullOnDelete();
            $table->string('price_list_name', 120)->nullable()->after('price_list_id');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreignId('price_list_item_id')->nullable()->after('unit_id')->constrained('price_list_items')->nullOnDelete();
            $table->decimal('price_min_quantity', 12, 4)->nullable()->after('price_list_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_item_id');
            $table->dropColumn('price_min_quantity');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropColumn('price_list_name');
        });
        Schema::table('price_list_items', function (Blueprint $table) {
            $table->dropUnique('price_list_product_unit_quantity_unique');
            $table->unique(['price_list_id', 'product_id', 'unit_id'], 'price_list_product_unit_unique');
            $table->dropIndex('price_list_items_price_list_id_index');
        });
    }
};
