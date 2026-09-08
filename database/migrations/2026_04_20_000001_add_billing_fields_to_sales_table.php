<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_number', 50)->nullable()->after('id');

            // Snapshot de datos de facturación (por si el cliente cambia después)
            $table->string('billing_name')->nullable()->after('client_id');
            $table->string('billing_business_name')->nullable()->after('billing_name');
            $table->string('billing_ruc', 30)->nullable()->after('billing_business_name');
            $table->string('billing_phone', 50)->nullable()->after('billing_ruc');
            $table->string('billing_email')->nullable()->after('billing_phone');
            $table->string('billing_address', 500)->nullable()->after('billing_email');

            $table->date('due_date')->nullable()->after('date');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number',
                'billing_name',
                'billing_business_name',
                'billing_ruc',
                'billing_phone',
                'billing_email',
                'billing_address',
                'due_date',
                'notes',
            ]);
        });
    }
};

