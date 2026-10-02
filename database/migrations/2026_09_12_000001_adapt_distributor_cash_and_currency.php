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
            $table->string('currency', 3)->default('NIO')->after('payment_type');
            $table->decimal('exchange_rate', 14, 6)->default(1)->after('currency');
        });
        Schema::table('credit_payments', function (Blueprint $table): void {
            $table->string('currency', 3)->default('NIO')->after('amount');
            $table->decimal('exchange_rate', 14, 6)->default(1)->after('currency');
        });
        Schema::table('purchases', function (Blueprint $table): void {
            $table->string('funding_source', 30)->default('external')->after('payment_type');
            $table->index(['caja_session_id', 'funding_source', 'status', 'payment_type'], 'purchases_closing_source_idx');
        });

        Schema::table('operational_expenses', function (Blueprint $table): void {
            $table->string('funding_source', 30)->default('external')->after('payment_method');
            $table->string('currency', 3)->default('NIO')->after('amount');
            $table->decimal('exchange_rate', 14, 6)->default(1)->after('currency');
            $table->index(['caja_session_id', 'funding_source', 'status', 'payment_method'], 'expenses_closing_source_idx');
        });
        DB::table('purchases')->whereNotNull('caja_session_id')->update(['funding_source' => 'sales_cash']);
        DB::table('operational_expenses')->whereNotNull('caja_session_id')->update(['funding_source' => 'sales_cash']);

        Schema::table('caja_sessions', function (Blueprint $table): void {
            $table->string('currency', 3)->default('NIO')->after('opening_amount');
            $table->index(['branch_id', 'status', 'opened_at'], 'cash_sessions_branch_history_idx');
        });

        Schema::table('arqueos', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('NIO')->after('branch_id');
            $table->timestamp('closed_at')->nullable()->after('currency');
            $table->string('snapshot_hash', 64)->nullable()->after('details');
            $table->index(['branch_id', 'closed_at'], 'arqueos_branch_closed_idx');
        });

        DB::table('modules')->updateOrInsert(['slug' => 'gastos'], [
            'name' => 'Gastos', 'description' => 'Gastos operativos independientes', 'icon' => '🧾',
            'route' => 'gastos.index', 'is_active' => true, 'sort_order' => 7,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            DB::table('permissions')->updateOrInsert(['slug' => "gastos.{$action}"], [
                'name' => ucfirst($action).' gastos', 'module' => 'gastos', 'action' => $action,
                'description' => ucfirst($action).' gastos operativos', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $admin = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($admin) {
            foreach (DB::table('permissions')->where('module', 'gastos')->pluck('id') as $permission) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('module', 'gastos')->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        if (Schema::hasTable('permission_user')) {
            DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
        }
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        DB::table('modules')->where('slug', 'gastos')->delete();
        Schema::table('credit_payments', fn (Blueprint $table) => $table->dropColumn(['currency', 'exchange_rate']));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['currency', 'exchange_rate']));
        Schema::table('arqueos', function (Blueprint $table): void {
            $table->dropIndex('arqueos_branch_closed_idx');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['currency', 'closed_at', 'snapshot_hash']);
        });
        Schema::table('caja_sessions', function (Blueprint $table): void {
            $table->dropIndex('cash_sessions_branch_history_idx');
            $table->dropColumn('currency');
        });
        Schema::table('operational_expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_closing_source_idx');
            $table->dropColumn(['funding_source', 'currency', 'exchange_rate']);
        });
        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropIndex('purchases_closing_source_idx');
            $table->dropColumn('funding_source');
        });
    }
};
