<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return $this->prefix.str_pad($this->current_number, $this->padding, '0', STR_PAD_LEFT);
    }

    public function incrementNumber()
    {
        $number = $this->getNextNumber();
        $this->increment('current_number');

        return $number;
    }

    public static function getNext($type)
    {
        return DB::transaction(function () use ($type) {
            $defaults = [
                'factura' => ['prefix' => 'FAC-', 'padding' => 6],
                'compra' => ['prefix' => 'COM-', 'padding' => 6],
                'cotizacion' => ['prefix' => 'COT-', 'padding' => 6],
                'recibo' => ['prefix' => 'REC-', 'padding' => 6],
                'ajuste' => ['prefix' => 'AJU-', 'padding' => 6],
                'asiento' => ['prefix' => 'POL-', 'padding' => 6],
            ];

            if (isset($defaults[$type])) {
                static::firstOrCreate(['type' => $type], $defaults[$type] + [
                    'current_number' => 1,
                    'is_active' => true,
                ]);
            }

            $sequence = static::byType($type)->active()->lockForUpdate()->first();
            if (! $sequence) {
                throw new \RuntimeException("No existe una secuencia activa para: {$type}");
            }

            return $sequence->incrementNumber();
        });
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
