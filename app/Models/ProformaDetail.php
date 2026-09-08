<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaDetail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'proforma_id',
        'product_id',
        'product_name',
        'unit_id',
        'unit_factor',
        'base_quantity',
        'price_list_item_id',
        'price_min_quantity',
        'quantity',
        'price',
        'discount',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_factor' => 'decimal:6',
            'base_quantity' => 'decimal:4',
            'price_min_quantity' => 'decimal:4',
            'price' => 'decimal:2',
        ];
    }

    public function proforma()
    {
        return $this->belongsTo(Proforma::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function priceListItem()
    {
        return $this->belongsTo(PriceListItem::class);
    }
}
