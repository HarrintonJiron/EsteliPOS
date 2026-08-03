<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->unique()->after('payment_status')
                ->constrained('sales')->nullOnDelete();
            $table->timestamp('invoiced_at')->nullable()->after('sale_id');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('description')->nullable()->after('product_id');
            $table->decimal('quantity', 10, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropColumn('invoiced_at');
        });

        DB::table('sale_details')->whereNull('product_id')->delete();

        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
            $table->integer('quantity')->change();
            $table->dropColumn('description');
        });
    }
};
