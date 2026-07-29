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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_pct', 5, 2)->default(0)->after('sale_price')->comment('Descuento automático % al agregar al ticket/factura');
            $table->string('discount_label', 80)->nullable()->after('discount_pct')->comment('Etiqueta de la promoción');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_pct', 'discount_label']);
        });
    }
};
