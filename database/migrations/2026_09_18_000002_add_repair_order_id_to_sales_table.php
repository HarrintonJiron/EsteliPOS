<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('repair_order_id')->nullable()->after('client_id')->constrained('repair_orders')->nullOnDelete();
            $table->unique('repair_order_id', 'sales_repair_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_repair_order_unique');
            $table->dropConstrainedForeignId('repair_order_id');
        });
    }
};
