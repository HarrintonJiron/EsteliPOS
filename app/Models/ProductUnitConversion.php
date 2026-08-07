<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnitConversion extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'factor_to_base',
        'sale_price',
        'is_default_sale_unit',
    ];

    protected function casts(): array
    {
        return [
            'factor_to_base' => 'decimal:6',
            'sale_price' => 'decimal:2',
            'is_default_sale_unit' => 'boolean',
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
}
