<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->foreignId('caja_session_id')->nullable()->after('user_id')->constrained('caja_sessions')->nullOnDelete();
            $table->timestamp('payment_received_at')->nullable()->after('payment_status');
            $table->index(['caja_session_id', 'payment_type', 'payment_received_at'], 'repair_orders_cash_session_payment_index');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table): void {
            $table->dropIndex('repair_orders_cash_session_payment_index');
            $table->dropColumn('payment_received_at');
            $table->dropConstrainedForeignId('caja_session_id');
        });
    }
};
