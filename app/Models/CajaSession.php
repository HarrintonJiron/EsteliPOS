<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaSession extends Model
{
    use HasFactory;

    protected $table = 'caja_sessions';

    protected $fillable = [
        'date', 'opened_at', 'opened_by', 'branch_id', 'opening_amount', 'closed_at', 'closed_by', 'status', 'open_guard',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (CajaSession $session): void {
            $session->open_guard = $session->status === 'open' ? 'C'.$session->opened_by : null;
        });
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function operationalExpenses()
    {
        return $this->hasMany(OperationalExpense::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public static function currentForUser(?int $userId): ?self
    {
        return $userId ? self::query()->where('opened_by', $userId)->where('status', 'open')->latest('id')->first() : null;
    }
}
