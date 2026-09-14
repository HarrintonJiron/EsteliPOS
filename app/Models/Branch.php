<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    public const TYPES = [
        'matriz' => 'Casa matriz',
        'sucursal' => 'Sucursal',
        'bodega' => 'Bodega',
        'patio' => 'Patio de materiales',
    ];

    protected $fillable = [
        'code',
        'name',
        'type',
        'city',
        'address',
        'phone',
        'manager_name',
        'warehouse_id',
        'price_list_id',
        'cost_center_id',
        'is_active',
        'share_percent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'share_percent' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
