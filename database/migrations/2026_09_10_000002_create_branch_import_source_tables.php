<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('source_code', 100);
            $table->string('source_description');
            $table->decimal('source_stock', 16, 4);
            $table->decimal('source_cost', 16, 4);
            $table->decimal('sale_price_1', 16, 4);
            $table->decimal('sale_price_2', 16, 4)->default(0);
            $table->decimal('sale_price_3', 16, 4)->default(0);
            $table->decimal('sale_price_4', 16, 4)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('quantity_2', 16, 4)->default(0);
            $table->decimal('quantity_3', 16, 4)->default(0);
            $table->decimal('quantity_4', 16, 4)->default(0);
            $table->boolean('automatic_price')->default(false);
            $table->string('quick_code', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('unit_name', 100);
            $table->string('location')->nullable();
            $table->string('category_name')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('product_line')->nullable();
            $table->date('expiration_date')->nullable();
            $table->decimal('minimum_stock', 16, 4)->default(0);
            $table->boolean('control_stock')->default(true);
            $table->boolean('show_in_sales')->default(true);
            $table->boolean('is_wood')->default(false);
            $table->decimal('board_feet', 16, 4)->default(0);
            $table->boolean('remote_print')->default(false);
            $table->string('source_file');
            $table->unsignedInteger('source_row');
            $table->json('raw_data');
            $table->timestamp('stock_imported_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'source_code'], 'branch_product_source_code_unique');
            $table->unique(['branch_id', 'product_id'], 'branch_product_product_unique');
            $table->index(['branch_id', 'quick_code'], 'branch_product_quick_code_index');
            $table->index(['branch_id', 'barcode'], 'branch_product_barcode_index');
        });

        Schema::create('branch_receivable_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('source_code', 100);
            $table->date('issue_date');
            $table->string('client_name');
            $table->decimal('source_total', 16, 4);
            $table->decimal('source_paid', 16, 4);
            $table->decimal('source_balance', 16, 4);
            $table->date('due_date');
            $table->string('source_file');
            $table->unsignedInteger('source_row');
            $table->json('raw_data');
            $table->timestamps();

            $table->unique(['branch_id', 'source_code'], 'branch_receivable_source_code_unique');
            $table->unique('sale_id', 'branch_receivable_sale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_receivable_sources');
        Schema::dropIfExists('branch_product_sources');
    }
};
