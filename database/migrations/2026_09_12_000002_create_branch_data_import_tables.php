<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_data_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20);
            $table->string('products_sha256', 64);
            $table->string('receivables_sha256', 64);
            $table->string('status', 20)->default('completed');
            $table->json('summary');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(
                ['branch_id', 'mode', 'products_sha256', 'receivables_sha256'],
                'branch_import_source_unique',
            );
        });

        Schema::create('branch_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('legacy_code', 100);
            $table->string('source_name');
            $table->timestamps();

            $table->unique(['branch_id', 'legacy_code']);
            $table->unique(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product_mappings');
        Schema::dropIfExists('branch_data_imports');
    }
};
