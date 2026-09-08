<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bonus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'amount',
        'date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'performance' => 'Desempeño',
            'sales' => 'Ventas',
            'attendance' => 'Asistencia',
            'christmas' => 'Navideño',
            'productivity' => 'Productividad',
            'other' => 'Otro',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'paid' => 'Pagado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'paid' => 'green',
            default => 'gray',
        };
    }
}
