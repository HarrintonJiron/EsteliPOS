<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'cedula',
        'position',
        'salary',
        'hourly_rate',
        'phone',
        'address',
        'hire_date',
        'contract_type',
        'payment_frequency',
        'is_active',
        'emergency_contact',
        'emergency_phone',
        'bank_account',
        'bank_name',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    public function getContractTypeLabelAttribute(): string
    {
        return match ($this->contract_type) {
            'full_time' => 'Tiempo Completo',
            'part_time' => 'Medio Tiempo',
            'temporary' => 'Temporal',
            'seasonal' => 'Por Temporada',
            default => $this->contract_type,
        };
    }

    public function getPaymentFrequencyLabelAttribute(): string
    {
        return match ($this->payment_frequency) {
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
            default => $this->payment_frequency,
        };
    }

    public function getYearsOfServiceAttribute(): int
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }
}
