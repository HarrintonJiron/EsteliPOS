<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RepairOrder extends Model
{
    protected $fillable = [
        'order_number',
        'client_id',
        'client_name',
        'client_phone',
        'client_email',
        'device_brand',
        'device_model',
        'device_color',
        'device_imei',
        'device_password',
        'lock_type',
        'accessories',
        'problem_description',
        'diagnosis',
        'repair_notes',
        'include_warranty_policy',
        'warranty_days',
        'warranty_policy',
        'status',
        'priority',
        'technician_id',
        'user_id',
        'received_date',
        'received_time',
        'estimated_date',
        'estimated_time',
        'delivered_date',
        'delivered_time',
        'labor_cost',
        'parts_cost',
        'total',
        'discount_amount',
        'discount_percentage',
        'advance_payment',
        'payment_type',
        'payment_status',
        'sale_id',
        'invoiced_at',
    ];

    protected $casts = [
        'include_warranty_policy' => 'boolean',
        'received_date' => 'date',
        'estimated_date' => 'date',
        'delivered_date' => 'date',
        'invoiced_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(RepairOrderItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function estimatedDeliveryAt(): ?Carbon
    {
        if (! $this->estimated_date) {
            return null;
        }

        return $this->estimated_date->copy()->setTimeFromTimeString(
            $this->estimated_time ?: '23:59:59'
        );
    }

    public function scheduleLabel(): string
    {
        if ($this->status === 'delivered') {
            if (! $this->estimatedDeliveryAt() || ! $this->delivered_date) {
                return 'Entregada';
            }

            $deliveredAt = $this->delivered_date->copy()->setTimeFromTimeString(
                $this->delivered_time ?: '23:59:59'
            );

            return $deliveredAt->lessThanOrEqualTo($this->estimatedDeliveryAt())
                ? 'Entregada a tiempo'
                : 'Entregada tarde';
        }

        if ($this->status === 'cancelled') {
            return 'Cancelada';
        }

        if (! $this->estimatedDeliveryAt()) {
            return 'Sin estimar';
        }

        return $this->estimatedDeliveryAt()->isPast() ? 'Atrasada' : 'A tiempo';
    }

    public function scheduleColor(): string
    {
        return match ($this->scheduleLabel()) {
            'A tiempo' => 'bg-emerald-100 text-emerald-700',
            'Atrasada' => 'bg-red-100 text-red-700',
            'Entregada', 'Entregada a tiempo' => 'bg-blue-100 text-blue-700',
            'Entregada tarde' => 'bg-orange-100 text-orange-700',
            'Cancelada' => 'bg-slate-200 text-slate-600',
            default => 'bg-amber-100 text-amber-700',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'received' => 'Recibido',
            'diagnosing' => 'Diagnóstico',
            'waiting_parts' => 'Esp. Repuestos',
            'in_repair' => 'En Reparación',
            'ready' => 'Listo',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'received' => 'bg-slate-100 text-slate-700',
            'diagnosing' => 'bg-blue-100 text-blue-700',
            'waiting_parts' => 'bg-amber-100 text-amber-700',
            'in_repair' => 'bg-indigo-100 text-indigo-700',
            'ready' => 'bg-green-100 text-green-700',
            'delivered' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'low' => 'Baja',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => ucfirst($this->priority),
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'low' => 'bg-slate-100 text-slate-600',
            'normal' => 'bg-blue-100 text-blue-700',
            'high' => 'bg-orange-100 text-orange-700',
            'urgent' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function balance(): float
    {
        if ($this->payment_status === 'paid') {
            return 0;
        }

        return max(0, $this->netTotal() - (float) $this->advance_payment);
    }

    public function netTotal(): float
    {
        $gross = (float) $this->total;
        $percentageDiscount = $gross * ((float) ($this->discount_percentage ?? 0) / 100);

        return max(0, round($gross - $percentageDiscount - (float) ($this->discount_amount ?? 0), 2));
    }
}
