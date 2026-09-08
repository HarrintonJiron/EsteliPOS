<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_sessions', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('opened_by')->constrained('branches')->nullOnDelete();
        });

        DB::table('caja_sessions')->where('status', 'open')->orderBy('id')->each(function ($session): void {
            DB::table('caja_sessions')->where('id', $session->id)->update([
                'open_guard' => $session->opened_by ? 'C'.$session->opened_by : 'S'.$session->id,
            ]);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('caja_session_id')->nullable()->after('user_id')->constrained('caja_sessions')->nullOnDelete();
        });
        Schema::table('credit_payments', function (Blueprint $table): void {
            $table->foreignId('caja_session_id')->nullable()->after('user_id')->constrained('caja_sessions')->nullOnDelete();
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->enum('status', ['present', 'late', 'absent', 'leave'])->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('performance_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('evaluation_date');
            $table->unsignedTinyInteger('score');
            $table->string('period', 30);
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'evaluation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
        Schema::dropIfExists('attendance_records');
        Schema::table('credit_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('caja_session_id'));
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('caja_session_id'));
        Schema::table('caja_sessions', fn (Blueprint $table) => $table->dropConstrainedForeignId('branch_id'));
    }
};
