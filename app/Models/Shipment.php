<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    public const DEPARTMENTS = ['Boaco', 'Carazo', 'Chinandega', 'Chontales', 'Costa Caribe Norte', 'Costa Caribe Sur', 'Estelí', 'Granada', 'Jinotega', 'León', 'Madriz', 'Managua', 'Masaya', 'Matagalpa', 'Nueva Segovia', 'Río San Juan', 'Rivas'];

    public const STATUSES = ['pending', 'prepared', 'shipped', 'delivered', 'cancelled'];

    protected $fillable = ['number', 'sale_id', 'client_id', 'user_id', 'recipient_name', 'recipient_phone', 'department', 'municipality', 'address', 'reference', 'carrier', 'tracking_number', 'shipping_cost', 'status', 'shipped_at', 'delivered_at', 'notes'];

    protected $casts = ['shipping_cost' => 'decimal:2', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime'];

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
