<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique()->nullable();

            // Client
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('client_name');
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email')->nullable();

            // Device
            $table->string('device_brand', 60);
            $table->string('device_model', 100);
            $table->string('device_color', 50)->nullable();
            $table->string('device_imei', 60)->nullable();
            $table->string('device_password', 100)->nullable();
            $table->text('accessories')->nullable();  // what came with the device

            // Technical
            $table->text('problem_description');          // reported by client
            $table->text('diagnosis')->nullable();         // found by technician
            $table->text('repair_notes')->nullable();      // internal technician notes

            // Workflow
            $table->enum('status', [
                'received',
                'diagnosing',
                'waiting_parts',
                'in_repair',
                'ready',
                'delivered',
                'cancelled',
            ])->default('received');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Dates
            $table->date('received_date');
            $table->date('estimated_date')->nullable();
            $table->date('delivered_date')->nullable();

            // Financials
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('advance_payment', 12, 2)->default(0);
            $table->enum('payment_type', ['cash', 'card', 'transfer'])->default('cash');
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');

            $table->timestamps();
        });

        Schema::create('repair_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained('repair_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_order_items');
        Schema::dropIfExists('repair_orders');
    }
};
