<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'caja_session_id',
        'repair_order_id',
        'account_id',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cajaSession()
    {
        return $this->belongsTo(CajaSession::class);
    }

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'source_id')->where('source_type', self::class);
    }

    public function currentJournalEntry(): ?JournalEntry
    {
        return $this->journalEntries()->where('status', JournalEntry::STATUS_POSTED)->latest('id')->first();
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', self::STATUS_REGISTERED);
    }

    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_REGISTERED => 'Registrado',
            self::STATUS_CANCELLED => 'Anulado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-amber-100 text-amber-700',
            self::STATUS_REGISTERED => 'bg-emerald-100 text-emerald-700',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia',
            'card' => 'Tarjeta',
            'other' => 'Otro',
            default => 'N/A',
        };
    }
}