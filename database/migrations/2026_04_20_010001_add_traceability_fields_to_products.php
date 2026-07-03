<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Campos para trazabilidad de agroquímicos y productos agrícolas
            $table->string('lot', 100)->nullable()->after('unit');
            $table->date('expiry_date')->nullable()->after('lot');
            $table->string('location', 255)->nullable()->after('expiry_date');
            $table->integer('low_stock_threshold')->default(10)->after('location');
            $table->string('registration_number', 100)->nullable()->after('low_stock_threshold'); // Registro sanitario para agroquímicos
            $table->string('active_ingredient', 255)->nullable()->after('registration_number'); // Ingrediente activo
            $table->string('concentration', 100)->nullable()->after('active_ingredient'); // Concentración (ej: 50%, 20kg/L)
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active')->after('concentration');
            $table->text('observations')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'lot',
                'expiry_date',
                'location',
                'low_stock_threshold',
                'registration_number',
                'active_ingredient',
                'concentration',
                'status',
                'observations',
            ]);
        });
    }
};
