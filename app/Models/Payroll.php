<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'gross_salary',
        'bonuses',
        'inss_deduction',
        'ir_deduction',
        'deductions',
        'loan_payments',
        'net_salary',
        'status',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'bonuses' => 'decimal:2',
            'inss_deduction' => 'decimal:2',
            'ir_deduction' => 'decimal:2',
            'deductions' => 'decimal:2',
            'loan_payments' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
