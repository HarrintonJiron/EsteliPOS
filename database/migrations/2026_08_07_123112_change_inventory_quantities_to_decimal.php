<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 16, 4)->default(0)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('quantity', 16, 4)->change();
            $table->decimal('stock_after', 16, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('stock_after')->nullable()->change();
        });
    }
};
