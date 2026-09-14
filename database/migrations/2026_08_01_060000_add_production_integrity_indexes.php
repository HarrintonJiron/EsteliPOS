<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('sales')
            ->select('invoice_number')
            ->whereNotNull('invoice_number')
            ->groupBy('invoice_number')
            ->havingRaw('COUNT(*) > 1')
            ->value('invoice_number');

        if ($duplicate !== null) {
            throw new RuntimeException(
                "No se puede proteger la numeración: la factura {$duplicate} está duplicada. Corrija los duplicados antes de migrar."
            );
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('invoice_number', 'sales_invoice_number_unique');
            $table->index(['date', 'status'], 'sales_date_status_index');
            $table->index(['client_id', 'payment_type', 'status', 'due_date'], 'sales_credit_portfolio_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['date', 'status'], 'purchases_date_status_index');
            $table->index(['supplier_id', 'date'], 'purchases_supplier_date_index');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'inventory_type_created_index');
            $table->index(['product_id', 'created_at'], 'inventory_product_created_index');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(['date', 'status'], 'journal_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('journal_date_status_index');
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_type_created_index');
            $table->dropIndex('inventory_product_created_index');
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_date_status_index');
            $table->dropIndex('purchases_supplier_date_index');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_number_unique');
            $table->dropIndex('sales_date_status_index');
            $table->dropIndex('sales_credit_portfolio_index');
        });
    }
};
