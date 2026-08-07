<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('gross_salary', 10, 2)->default(0)->after('base_salary');
            $table->decimal('inss_deduction', 10, 2)->default(0)->after('bonuses');
            $table->decimal('ir_deduction', 10, 2)->default(0)->after('inss_deduction');
            $table->decimal('loan_payments', 10, 2)->default(0)->after('deductions');
            $table->enum('status', ['pending', 'paid'])->default('pending')->after('net_salary');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'month', 'year']);
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn([
                'gross_salary',
                'inss_deduction',
                'ir_deduction',
                'loan_payments',
                'status',
                'paid_at',
            ]);
        });
    }
};
