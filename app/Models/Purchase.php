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
        'supplier_id',
        'user_id',
        'warehouse_id',
        'date',
        'subtotal',
        'tax_total',
        'total',
        'status',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
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
