<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'unit_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    public function typeLabel(): string
    {
        return match ($this->unit_type) {
            'volume' => 'Volumen',
            'weight' => 'Peso',
            'length' => 'Longitud',
            'area' => 'Área',
            'package' => 'Empaque',
            default => 'Unidad',
        };
    }
}
