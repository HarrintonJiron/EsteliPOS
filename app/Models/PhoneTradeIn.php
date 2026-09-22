<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneTradeIn extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'trade_in_value',
        'was_returning_phone',
        'notes',
    ];

    protected $casts = [
        'trade_in_value' => 'decimal:2',
        'was_returning_phone' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
