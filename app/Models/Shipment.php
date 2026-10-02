<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    public const DEPARTMENTS = ['Boaco', 'Carazo', 'Chinandega', 'Chontales', 'Costa Caribe Norte', 'Costa Caribe Sur', 'Estelí', 'Granada', 'Jinotega', 'León', 'Madriz', 'Managua', 'Masaya', 'Matagalpa', 'Nueva Segovia', 'Río San Juan', 'Rivas'];

    public const STATUSES = ['pending', 'prepared', 'shipped', 'delivered', 'cancelled'];

    public const STATUS_LABELS = [
        'pending' => 'Pendiente',
        'prepared' => 'Preparado',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-100 text-amber-800',
            'prepared' => 'bg-sky-100 text-sky-800',
            'shipped' => 'bg-indigo-100 text-indigo-800',
            'delivered' => 'bg-emerald-100 text-emerald-800',
            'cancelled' => 'bg-slate-200 text-slate-600',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    protected $fillable = ['number', 'sale_id', 'client_id', 'user_id', 'recipient_name', 'recipient_phone', 'department', 'municipality', 'address', 'is_fragile', 'reference', 'carrier', 'tracking_number', 'shipping_cost', 'status', 'shipped_at', 'delivered_at', 'notes'];

    protected $casts = ['shipping_cost' => 'decimal:2', 'is_fragile' => 'boolean', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
