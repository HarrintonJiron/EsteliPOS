<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->time('received_time')->nullable()->after('received_date')->comment('Hora en que se recibió el equipo');
            $table->time('estimated_delivery_time')->nullable()->after('estimated_date')->comment('Hora estimada de entrega (sin fecha obligatoria)');
            $table->time('delivered_time')->nullable()->after('delivered_date')->comment('Hora en que se entregó el equipo al cliente');
            $table->boolean('warranty_enabled')->default(true)->after('payment_status')->comment('Mostrar garantía en el ticket');
            $table->text('warranty_text')->nullable()->after('warranty_enabled')->comment('Texto editable de la garantía a mostrar en el ticket');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropColumn([
                'received_time',
                'estimated_delivery_time',
                'delivered_time',
                'warranty_enabled',
                'warranty_text',
            ]);
        });
    }
};
