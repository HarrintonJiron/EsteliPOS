<?php

namespace App\Models;

use App\Services\CreditService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_NATURAL = 'natural';
    public const TYPE_COMPANY = 'company';

    protected $fillable = [
        'code',
        'name',
        'client_type',
        'business_name',
        'ruc',
        'cedula',
        'phone',
        'email',
        'address',
        'taxpayer_type',
        'department',
        'municipality',
        'status',
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
            'client_type' => 'string',
            'status' => 'string',
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

    public function getLegalNameAttribute(): string
    {
        return $this->isCompany() ? ($this->business_name ?: $this->name) : $this->name;
    }

    public function getDocumentLabelAttribute(): string
    {
        return $this->isCompany() ? 'RUC' : 'Cédula';
    }

    public function getDocumentNumberAttribute(): ?string
    {
        return $this->isCompany() ? $this->ruc : $this->cedula;
    }

    public function getFormattedLocationAttribute(): string
    {
        return collect([$this->municipality, $this->department])->filter()->implode(', ');
    }

    public function isCompany(): bool
    {
        return $this->client_type === self::TYPE_COMPANY;
    }

    public function isNatural(): bool
    {
        return ! $this->isCompany();
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
