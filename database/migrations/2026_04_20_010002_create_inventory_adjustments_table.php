<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['increase', 'decrease', 'count'])->comment('increase: aumento, decrease: disminución, count: ajuste por conteo físico');
            $table->integer('quantity'); // Cantidad ajustada (positiva o negativa según el tipo)
            $table->integer('stock_before'); // Stock antes del ajuste
            $table->integer('stock_after'); // Stock después del ajuste
            $table->text('reason')->comment('Motivo del ajuste: vencimiento, daño, conteo físico, etc.');
            $table->string('reference', 100)->nullable()->comment('Referencia interna o número de documento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
