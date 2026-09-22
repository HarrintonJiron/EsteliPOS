<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cobros de reparaciones de clientes sin registrar (mostrador): client_id ya no es obligatorio.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite no puede modificar la restricción de la columna; las pruebas usan esquema nuevo.
            Schema::table('repair_credit_payments', function (Blueprint $table) {
                $table->foreignId('client_id')->nullable()->change();
            });
        } else {
            Schema::table('repair_credit_payments', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
            Schema::table('repair_credit_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->change();
                $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            });
        }

        // Estados de pago coherentes con lo realmente cobrado:
        // "paid" solo si hay total y está cubierto; sin cobros = pending; algo cobrado = partial.
        $paymentsSub = '(SELECT COALESCE(SUM(amount), 0) FROM repair_credit_payments WHERE repair_credit_payments.repair_order_id = repair_orders.id)';

        DB::table('repair_orders')->update(['payment_status' => DB::raw(
            "CASE
                WHEN total > 0 AND (advance_payment + {$paymentsSub}) >= total - 0.00001 THEN 'paid'
                WHEN (advance_payment + {$paymentsSub}) > 0 THEN 'partial'
                ELSE 'pending'
            END"
        )]);
    }

    public function down(): void
    {
        // La conciliación de estados no se revierte. client_id vuelve a ser obligatorio solo si no hay filas sin cliente.
        if (DB::table('repair_credit_payments')->whereNull('client_id')->exists()) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('repair_credit_payments', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
            Schema::table('repair_credit_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable(false)->change();
                $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            });
        }
    }
};
