<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proformas', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('user_id')->constrained('price_lists')->nullOnDelete();
            $table->string('price_list_name', 120)->nullable()->after('price_list_id');
            $table->foreignId('sale_id')->nullable()->after('price_list_name')->constrained('sales')->nullOnDelete();
        });

        Schema::table('proforma_details', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            $table->decimal('unit_factor', 16, 6)->nullable()->after('quantity');
            $table->decimal('base_quantity', 16, 4)->nullable()->after('unit_factor');
            $table->foreignId('price_list_item_id')->nullable()->after('base_quantity')->constrained('price_list_items')->nullOnDelete();
            $table->decimal('price_min_quantity', 12, 4)->nullable()->after('price_list_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('proforma_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_item_id');
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['unit_factor', 'base_quantity', 'price_min_quantity']);
        });
        Schema::table('proformas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropConstrainedForeignId('sale_id');
            $table->dropColumn('price_list_name');
        });
    }
};
