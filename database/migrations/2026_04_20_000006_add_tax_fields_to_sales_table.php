<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('tax_included')->default(false)->after('payment_type');
            $table->decimal('tax_rate', 5, 4)->default(0.1500)->after('tax_included');
            $table->decimal('subtotal', 10, 2)->default(0)->after('date');
            $table->decimal('tax_total', 10, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['tax_included', 'tax_rate', 'subtotal', 'tax_total']);
        });
    }
};

