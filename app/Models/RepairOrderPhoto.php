<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairOrderPhoto extends Model
{
    protected $fillable = ['repair_order_id', 'path'];

    protected $appends = ['url'];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function getUrlAttribute(): string
    {
        return route('reparaciones.photos.show', $this);
    }
}
