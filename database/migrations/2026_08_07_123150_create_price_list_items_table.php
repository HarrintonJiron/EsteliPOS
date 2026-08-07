<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('min_quantity', 12, 4)->default(1);
            $table->timestamps();

            $table->unique(['price_list_id', 'product_id', 'unit_id'], 'price_list_product_unit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
