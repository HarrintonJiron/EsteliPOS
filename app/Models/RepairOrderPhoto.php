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
        $prefix = $this->repairOrder?->order_type === 'jewelry' ? 'joyeria' : 'reparaciones';

        return route($prefix.'.photos.show', $this);
    }
}
