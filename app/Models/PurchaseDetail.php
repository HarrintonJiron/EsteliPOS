<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'unit_id',
        'quantity',
        'base_quantity',
        'price',
        'subtotal',
        'tax_rate',
        'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryQuantity(): float
    {
        return (float) ($this->base_quantity ?? $this->quantity);
    }
}
