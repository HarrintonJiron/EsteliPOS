<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationItem extends Model
{
    protected $fillable = ['reservation_id', 'product_id', 'quantity', 'unit_price', 'subtotal'];

    protected $casts = ['quantity' => 'decimal:4', 'unit_price' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
