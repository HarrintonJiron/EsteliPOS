<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->time('received_time')->nullable()->after('received_date');
            $table->time('delivered_time')->nullable()->after('delivered_date');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->dropColumn(['received_time', 'delivered_time']);
        });
    }
};
