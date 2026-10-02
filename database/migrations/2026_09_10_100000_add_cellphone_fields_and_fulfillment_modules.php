<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('condition', 20)->nullable()->after('description');
            $table->string('brand', 80)->nullable()->after('condition');
            $table->string('model', 120)->nullable()->after('brand');
            $table->string('color', 60)->nullable()->after('model');
            $table->string('imei', 30)->nullable()->unique()->after('color');
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->dateTime('reserved_at');
            $table->dateTime('expires_at')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->unique(['reservation_id', 'product_id']);
            $table->index(['product_id', 'reservation_id']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name', 150);
            $table->string('recipient_phone', 30)->nullable();
            $table->string('department', 40);
            $table->string('municipality', 80)->nullable();
            $table->text('address');
            $table->text('reference')->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['department', 'status']);
        });

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('estimated_date');
        });

        Schema::create('repair_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained('repair_orders')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->dateTime('payment_date');
            $table->string('payment_type', 20)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE repair_orders MODIFY payment_type ENUM('cash','card','transfer','credit') NOT NULL DEFAULT 'cash'");
        }

        foreach ([
            ['name' => 'Apartados y envíos', 'slug' => 'operaciones_clientes', 'description' => 'Reservas, entregas y seguimiento al cliente', 'icon' => '🚚', 'route' => 'operaciones-clientes.index', 'sort_order' => 14],
        ] as $module) {
            DB::table('modules')->updateOrInsert(['slug' => $module['slug']], $module + [
                'dependencies' => null, 'required_permission' => 'apartados.view', 'is_core' => false,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach (['apartados' => ['view', 'create', 'edit', 'cancel', 'convert'], 'envios' => ['view', 'create', 'edit', 'delete']] as $module => $actions) {
            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(['slug' => "{$module}.{$action}"], [
                    'name' => ucfirst($module).' '.ucfirst($action), 'module' => $module, 'action' => $action,
                    'description' => "Permiso para {$action} en {$module}", 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        foreach (['apartado' => 'APT-', 'envio' => 'ENV-'] as $type => $prefix) {
            DB::table('number_sequences')->updateOrInsert(['type' => $type], ['prefix' => $prefix, 'current_number' => 1, 'padding' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        DB::table('price_lists')->updateOrInsert(['code' => 'ESPECIAL'], ['name' => 'Precio especial', 'description' => 'Precio especial o distribuidor', 'is_default' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        Cache::forget('modules.active');
        Cache::forget('modules.operaciones_clientes.active');

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($adminRoleId) {
            DB::table('permissions')->whereIn('module', ['apartados', 'envios'])->pluck('id')->each(fn ($permissionId) => DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $adminRoleId]));
            DB::table('modules')->where('slug', 'operaciones_clientes')->pluck('id')->each(fn ($moduleId) => DB::table('module_role')->insertOrIgnore(['module_id' => $moduleId, 'role_id' => $adminRoleId]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_credit_payments');
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn('due_date'));
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('reservation_items');
        Schema::dropIfExists('reservations');
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['imei']);
            $table->dropColumn(['condition', 'brand', 'model', 'color', 'imei']);
        });
    }
};
