<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'rate', 'is_default', 'is_active'];

    protected $casts = [
        'rate' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public static function defaultTax(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    public static function defaultRate(): float
    {
        return (float) (static::defaultTax()?->rate ?? 0);
    }
}
