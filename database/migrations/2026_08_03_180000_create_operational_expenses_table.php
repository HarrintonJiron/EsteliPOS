<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('caja_session_id')->nullable()->constrained('caja_sessions')->nullOnDelete();
            $table->foreignId('repair_order_id')->nullable()->constrained('repair_orders')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('registered');
            $table->timestamps();

            $table->index(['expense_date', 'status'], 'operational_expenses_date_status_idx');
            $table->index(['caja_session_id', 'payment_method'], 'operational_expenses_caja_payment_idx');
        });

        if (Schema::hasTable('accounts')) {
            $parentId = DB::table('accounts')->where('code', '6.1')->value('id');

            DB::table('accounts')->updateOrInsert(['code' => '6.1.99'], [
                'name' => 'Gastos Operativos Taller',
                'description' => 'Egresos operativos del módulo de reparaciones',
                'type' => 'expense',
                'nature' => 'debit',
                'parent_id' => $parentId,
                'level' => 3,
                'is_postable' => true,
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('permissions')) {
            foreach ([
                ['view_expenses', 'Ver gastos operativos'],
                ['create_expenses', 'Crear gastos operativos'],
                ['edit_expenses', 'Editar gastos operativos'],
                ['delete_expenses', 'Eliminar gastos operativos'],
            ] as [$action, $name]) {
                DB::table('permissions')->updateOrInsert(['slug' => "reparaciones.{$action}"], [
                    'name' => $name,
                    'module' => 'reparaciones',
                    'action' => $action,
                    'description' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (Schema::hasTable('roles') && Schema::hasTable('permission_role')) {
                $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
                if ($adminId) {
                    $permissionIds = DB::table('permissions')
                        ->whereIn('slug', [
                            'reparaciones.view_expenses',
                            'reparaciones.create_expenses',
                            'reparaciones.edit_expenses',
                            'reparaciones.delete_expenses',
                        ])
                        ->pluck('id');

                    foreach ($permissionIds as $permissionId) {
                        DB::table('permission_role')->insertOrIgnore([
                            'permission_id' => $permissionId,
                            'role_id' => $adminId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permission_role') && Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', [
                    'reparaciones.view_expenses',
                    'reparaciones.create_expenses',
                    'reparaciones.edit_expenses',
                    'reparaciones.delete_expenses',
                ])
                ->pluck('id');
            if ($permissionIds->isNotEmpty()) {
                DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
                if (Schema::hasTable('permission_user')) {
                    DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
                }
                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }

        if (Schema::hasTable('accounts')) {
            DB::table('accounts')->where('code', '6.1.99')->delete();
        }

        Schema::dropIfExists('operational_expenses');
    }
};