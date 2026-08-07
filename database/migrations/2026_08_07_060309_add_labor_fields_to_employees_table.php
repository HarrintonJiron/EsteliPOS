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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('cedula', 20)->nullable()->after('name');
            $table->date('hire_date')->nullable()->after('phone');
            $table->enum('contract_type', ['full_time', 'part_time', 'temporary', 'seasonal'])->default('full_time')->after('hire_date');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('salary');
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly'])->default('monthly')->after('hourly_rate');
            $table->boolean('is_active')->default(true)->after('payment_frequency');
            $table->string('emergency_contact')->nullable()->after('is_active');
            $table->string('emergency_phone')->nullable()->after('emergency_contact');
            $table->string('bank_account')->nullable()->after('emergency_phone');
            $table->string('bank_name')->nullable()->after('bank_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'cedula',
                'hire_date',
                'contract_type',
                'hourly_rate',
                'payment_frequency',
                'is_active',
                'emergency_contact',
                'emergency_phone',
                'bank_account',
                'bank_name',
            ]);
        });
    }
};
