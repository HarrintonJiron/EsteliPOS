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
        'quantity',
        'price',
        'discount',
        'subtotal',
    ];

    public function proforma()
    {
        return $this->belongsTo(Proforma::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
