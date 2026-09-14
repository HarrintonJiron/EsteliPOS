<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->uuid('request_token')->nullable()->unique()->after('id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('caja_session_id')->nullable()->after('user_id')
                ->constrained('caja_sessions')->nullOnDelete();
            $table->index(['caja_session_id', 'status', 'payment_type'], 'purchases_cash_session_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_cash_session_index');
            $table->dropConstrainedForeignId('caja_session_id');
        });

        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropUnique(['request_token']);
            $table->dropColumn('request_token');
        });
    }
};
