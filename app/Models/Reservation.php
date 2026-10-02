<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['number', 'client_id', 'user_id', 'branch_id', 'warehouse_id', 'reserved_at', 'expires_at', 'total', 'deposit', 'status', 'notes', 'sale_id'];

    protected $casts = ['reserved_at' => 'datetime', 'expires_at' => 'datetime', 'total' => 'decimal:2', 'deposit' => 'decimal:2'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(ReservationPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        $payments = (float) $this->payments()->where('type', 'payment')->sum('amount');
        $refunds = (float) $this->payments()->where('type', 'refund')->sum('amount');

        return max(0, round((float) $this->deposit + $payments - $refunds, 2));
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->total - $this->paid_amount, 2));
    }
}
