<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceBrand extends Model
{
    protected $fillable = [
        'name',
        'workshop_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWorkshop($query, string $type)
    {
        return $query->where('workshop_type', $type);
    }
}
