<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arqueo extends Model
{
    use HasFactory;

    protected $fillable = [
        'caja_session_id', 'date', 'user_id', 'branch_id', 'currency', 'closed_at', 'total_sales_count', 'total_sales_amount', 'cash_total', 'credit_payments_total', 'physical_total', 'difference', 'details', 'snapshot_hash',
    ];

    protected $casts = [
        'date' => 'date',
        'details' => 'array',
        'total_sales_amount' => 'decimal:2',
        'cash_total' => 'decimal:2',
        'credit_payments_total' => 'decimal:2',
        'physical_total' => 'decimal:2',
        'difference' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function cajaSession()
    {
        return $this->belongsTo(CajaSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los cierres de caja son inmutables.'));
        static::deleting(fn () => throw new \LogicException('Los cierres de caja son inmutables.'));
    }
}
