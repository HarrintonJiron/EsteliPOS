<?php

namespace App\Models;

use App\Services\CompanySettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    use HasFactory;

    protected $table = 'credit_payments';

    protected $fillable = [
        'request_token',
        'client_id',
        'sale_id',
        'amount',
        'currency',
        'exchange_rate',
        'payment_date',
        'payment_type',
        'reference_number',
        'notes',
        'user_id',
        'branch_id',
        'caja_session_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'exchange_rate' => 'decimal:6',
    ];

    protected static function booted(): void
    {
        static::creating(function (CreditPayment $payment): void {
            $payment->currency ??= app(CompanySettingsService::class)->get()['currency'];
            $payment->exchange_rate ??= 1;
        });
    }

    // Relaciones
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cajaSession()
    {
        return $this->belongsTo(CajaSession::class);
    }
}
