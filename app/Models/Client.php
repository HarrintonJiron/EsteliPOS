<?php

namespace App\Models;

use App\Services\CreditService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'business_name',
        'ruc',
        'phone',
        'email',
        'address',
        'credit_enabled',
        'credit_limit',
        'credit_days',
        'mora_enabled',
        'mora_rate',
        'mora_grace_days',
        'mora_max_pct',
    ];

    protected function casts(): array
    {
        return [
            'credit_enabled' => 'boolean',
            'credit_limit' => 'decimal:2',
            'credit_days' => 'integer',
            'mora_enabled' => 'boolean',
            'mora_rate' => 'decimal:2',
            'mora_grace_days' => 'integer',
            'mora_max_pct' => 'decimal:2',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function creditBalance(): float
    {
        return app(CreditService::class)->pendingDebt($this);
    }

    public function availableCredit(): float
    {
        return app(CreditService::class)->availableCredit($this);
    }

    public function isOverCreditLimit(): bool
    {
        if (! $this->credit_enabled || (float) $this->credit_limit <= 0) {
            return false;
        }

        return $this->creditBalance() > (float) $this->credit_limit;
    }
}
