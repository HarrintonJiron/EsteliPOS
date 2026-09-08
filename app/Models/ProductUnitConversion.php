<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnitConversion extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'equals_unit_id',
        'equals_quantity',
        'factor_to_base',
        'sale_price',
        'use_for_purchase',
        'use_for_sale',
        'is_default_purchase_unit',
        'is_default_sale_unit',
        'allow_fraction',
        'barcode',
    ];

    protected function casts(): array
    {
        return [
            'factor_to_base' => 'decimal:6',
            'equals_quantity' => 'decimal:6',
            'sale_price' => 'decimal:2',
            'use_for_purchase' => 'boolean',
            'use_for_sale' => 'boolean',
            'is_default_purchase_unit' => 'boolean',
            'is_default_sale_unit' => 'boolean',
            'allow_fraction' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function equalsUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'equals_unit_id');
    }
}
