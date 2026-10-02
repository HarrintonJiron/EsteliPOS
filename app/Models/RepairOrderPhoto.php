<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairOrderPhoto extends Model
{
    protected $fillable = ['repair_order_id', 'photo_path'];

    protected $appends = ['url'];

    /** Alias de compatibilidad con las primeras versiones del taller. */
    public function getPathAttribute(): ?string
    {
        return $this->photo_path;
    }

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function getUrlAttribute(): string
    {
        if ($this->repairOrder?->order_type === 'jewelry') {
            return route('joyeria.photos.show', $this->id, false);
        }

        return route('reparaciones.photos.show', [$this->repair_order_id, $this->id], false);
    }
}
