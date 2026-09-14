<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_order_id',
        'product_id',
        'service_id',
        'description',
        'quantity',
        'price',
        'subtotal',
        'item_type',
        'device_brand',
    ];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function service()
    {
        return $this->belongsTo(RepairService::class);
    }
}
