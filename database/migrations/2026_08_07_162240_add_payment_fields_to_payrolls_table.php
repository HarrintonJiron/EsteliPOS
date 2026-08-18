<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'gross_salary')) {
                $table->decimal('gross_salary', 10, 2)->default(0)->after('base_salary');
            }
            if (! Schema::hasColumn('payrolls', 'inss_deduction') && ! Schema::hasColumn('payrolls', 'inss_deductions')) {
                $table->decimal('inss_deduction', 10, 2)->default(0)->after('bonuses');
            }
            if (! Schema::hasColumn('payrolls', 'ir_deduction') && ! Schema::hasColumn('payrolls', 'ir_deductions')) {
                $table->decimal('ir_deduction', 10, 2)->default(0)->after('bonuses');
            }
            if (! Schema::hasColumn('payrolls', 'loan_payments')) {
                $table->decimal('loan_payments', 10, 2)->default(0)->after('deductions');
            }
            if (! Schema::hasColumn('payrolls', 'status') && ! Schema::hasColumn('payrolls', 'payment_status')) {
                $table->enum('status', ['pending', 'paid'])->default('pending')->after('net_salary');
            }
            if (! Schema::hasColumn('payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('net_salary');
            }
            if (! Schema::hasColumn('payrolls', 'paid_by') && ! Schema::hasColumn('payrolls', 'paid_by_user_id')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
        });

        $indexExists = DB::select(
            'SHOW INDEX FROM payrolls WHERE Key_name = ?',
            ['payrolls_employee_id_month_year_unique']
        );

        $hasDuplicatePayrolls = DB::table('payrolls')
            ->select('employee_id', 'month', 'year')
            ->groupBy('employee_id', 'month', 'year')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($indexExists === [] && ! $hasDuplicatePayrolls) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->unique(['employee_id', 'month', 'year']);
            });
        }
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
