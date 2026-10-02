<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->index('repair_order_id', 'sales_repair_order_index');
        });
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_repair_order_unique');
            $table->foreignId('repair_credit_payment_id')->nullable()->after('repair_order_id')
                ->constrained('repair_credit_payments')->nullOnDelete();
            $table->unique('repair_credit_payment_id', 'sales_repair_credit_payment_unique');
        });

        // Los cobros anteriores a esta migración ya tenían una venta vinculada.
        // Como antes solo se permitía una, se puede identificar por orden e importe.
        DB::table('sales')->whereNotNull('repair_order_id')
            ->where('notes', 'like', 'Cobro de reparación %')
            ->orderBy('id')->get(['id', 'repair_order_id', 'total'])
            ->each(function ($sale): void {
                $paymentId = DB::table('repair_credit_payments')
                    ->where('repair_order_id', $sale->repair_order_id)
                    ->where('amount', $sale->total)
                    ->orderBy('id')->value('id');
                if ($paymentId) {
                    DB::table('sales')->where('id', $sale->id)->update(['repair_credit_payment_id' => $paymentId]);
                }
            });
    }

    public function down(): void
    {
        if (DB::table('sales')->whereNotNull('repair_order_id')->select('repair_order_id')
            ->groupBy('repair_order_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede revertir: hay varios cobros vinculados a una reparación.');
        }

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_repair_credit_payment_unique');
            $table->dropConstrainedForeignId('repair_credit_payment_id');
            $table->unique('repair_order_id', 'sales_repair_order_unique');
        });
        Schema::table('sales', fn (Blueprint $table) => $table->dropIndex('sales_repair_order_index'));
    }
};
