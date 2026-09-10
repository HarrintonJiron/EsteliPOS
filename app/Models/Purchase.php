<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'supplier_id',
        'user_id',
        'branch_id',
        'caja_session_id',
        'warehouse_id',
        'date',
        'subtotal',
        'tax_total',
        'total',
        'status',
        'payment_type',
        'currency',
        'exchange_rate',
        'foreign_subtotal',
        'foreign_tax_total',
        'foreign_total',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'foreign_subtotal' => 'decimal:2',
            'foreign_tax_total' => 'decimal:2',
            'foreign_total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cajaSession(): BelongsTo
    {
        return $this->belongsTo(CajaSession::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function supplierPayment()
    {
        return $this->hasOne(SupplierPayment::class);
    }

    public function affectsInventory(): bool
    {
        return in_array($this->status, ['pending', 'completed'], true);
    }

    public function isOnCredit(): bool
    {
        if ($this->status === 'ordered') {
            return false;
        }

        return $this->status === 'pending' || $this->settlementType() === 'credit';
    }

    public function settlementType(): string
    {
        if ($this->status === 'pending') {
            return 'credit';
        }

        $paymentType = (string) ($this->payment_type ?: 'cash');

        return in_array($paymentType, ['cash', 'transfer', 'credit'], true)
            ? $paymentType
            : 'cash';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'ordered' => 'Pedido en proceso',
            'completed' => 'Pagada',
            'pending' => 'Por pagar',
            default => 'Anulada',
        };
    }

    public function isForeignCurrency(?string $companyCurrency = null): bool
    {
        $company = strtoupper($companyCurrency ?: 'NIO');

        return strtoupper((string) ($this->currency ?: $company)) !== $company;
    }

    public function currencySymbol(): string
    {
        return match (strtoupper((string) ($this->currency ?: 'NIO'))) {
            'USD' => 'US$',
            'EUR' => '€',
            default => 'C$',
        };
    }
}
