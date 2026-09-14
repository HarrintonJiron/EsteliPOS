<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('warehouse_id')->constrained('price_lists')->nullOnDelete();
        });
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->nullable()->after('quantity');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });
        foreach (['sales', 'purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('branch_id')->nullable()->after('user_id')->constrained('branches')->nullOnDelete();
                $table->index(['branch_id', 'date'], $tableName.'_branch_date_index');
            });
        }
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'payment_date'], 'credit_payments_branch_date_index');
        });

        DB::table('sales')->whereNull('branch_id')->update([
            'branch_id' => DB::raw('(SELECT cs.branch_id FROM caja_sessions cs WHERE cs.id = sales.caja_session_id)'),
        ]);
        DB::table('purchases')->whereNull('branch_id')->update([
            'branch_id' => DB::raw('(SELECT cs.branch_id FROM caja_sessions cs WHERE cs.id = purchases.caja_session_id)'),
        ]);
        DB::table('credit_payments')->whereNull('branch_id')->update([
            'branch_id' => DB::raw('(SELECT cs.branch_id FROM caja_sessions cs WHERE cs.id = credit_payments.caja_session_id)'),
        ]);

        foreach (['sales', 'purchases'] as $tableName) {
            DB::table($tableName)->whereNull('branch_id')->whereNotNull('warehouse_id')->update([
                'branch_id' => DB::raw("(SELECT b.id FROM branches b WHERE b.warehouse_id = {$tableName}.warehouse_id ORDER BY b.id LIMIT 1)"),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['credit_payments', 'purchases', 'sales'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropConstrainedForeignId('branch_id');
                $table->dropIndex($tableName.'_branch_date_index');
            });
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('branch_id'));
        Schema::table('warehouse_stocks', fn (Blueprint $table) => $table->dropColumn('purchase_price'));
        Schema::table('branches', fn (Blueprint $table) => $table->dropConstrainedForeignId('price_list_id'));
    }
};
