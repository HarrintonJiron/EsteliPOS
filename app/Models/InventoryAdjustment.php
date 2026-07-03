<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'reference',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'increase' => 'Aumento',
            'decrease' => 'Disminución',
            'count' => 'Ajuste por Conteo',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'increase' => 'green',
            'decrease' => 'red',
            'count' => 'blue',
            default => 'gray',
        };
    }
}
