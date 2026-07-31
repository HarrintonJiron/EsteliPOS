<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'prefix', 'current_number', 'padding', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNextNumber()
    {
        return $this->prefix . str_pad($this->current_number, $this->padding, '0', STR_PAD_LEFT);
    }

    public function incrementNumber()
    {
        $number = $this->getNextNumber();
        $this->increment('current_number');
        return $number;
    }

    public static function getNext($type)
    {
        $sequence = static::byType($type)->active()->first();
        if (!$sequence) {
            throw new \Exception("No active sequence found for type: {$type}");
        }
        return $sequence->incrementNumber();
    }

    public static function reset($type, $newNumber = 1)
    {
        $sequence = static::byType($type)->first();
        if ($sequence) {
            $sequence->update(['current_number' => $newNumber]);
        }
        return $sequence;
    }
}
