<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_credit_payments', function (Blueprint $table) {
            // Evita duplicar un abono a reparaciones si el formulario se envía dos veces.
            $table->string('request_token', 64)->nullable()->after('notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('repair_credit_payments', function (Blueprint $table) {
            $table->dropIndex(['request_token']);
            $table->dropColumn('request_token');
        });
    }
};
