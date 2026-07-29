<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('mora_enabled')->default(false)->after('credit_days');
            $table->decimal('mora_rate', 5, 2)->default(0)->after('mora_enabled')->comment('% de mora por día vencido');
            $table->unsignedSmallInteger('mora_grace_days')->default(0)->after('mora_rate')->comment('Días de gracia antes de cobrar mora');
            $table->decimal('mora_max_pct', 5, 2)->default(0)->after('mora_grace_days')->comment('Mora máxima acumulada como % del principal (0 = sin tope)');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['mora_enabled', 'mora_rate', 'mora_grace_days', 'mora_max_pct']);
        });
    }
};
