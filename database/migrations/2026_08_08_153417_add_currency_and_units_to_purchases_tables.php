<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('currency', 3)->default('NIO')->after('status');
            $table->decimal('exchange_rate', 14, 6)->default(1)->after('currency');
            $table->decimal('foreign_subtotal', 14, 2)->nullable()->after('exchange_rate');
            $table->decimal('foreign_tax_total', 14, 2)->nullable()->after('foreign_subtotal');
            $table->decimal('foreign_total', 14, 2)->nullable()->after('foreign_tax_total');
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            $table->decimal('base_quantity', 14, 4)->nullable()->after('quantity');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE purchase_details MODIFY quantity DECIMAL(14, 4) NOT NULL');
        } elseif ($driver === 'sqlite') {
            // Fresh sqlite schemas already pick up the create migration change when present;
            // for existing sqlite files recreate via table rebuild is unnecessary in tests.
        }
    }

    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('base_quantity');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE purchase_details MODIFY quantity INT NOT NULL');
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'exchange_rate',
                'foreign_subtotal',
                'foreign_tax_total',
                'foreign_total',
            ]);
        });
    }
};
