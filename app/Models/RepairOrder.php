<?php

namespace App\Models;

use App\Services\CompanySettingsService;
use Illuminate\Database\Eloquent\Model;

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
        'status',
        'priority',
        'technician_id',
        'user_id',
        'received_date',
        'received_time',
        'estimated_date',
        'estimated_delivery_time',
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
        'warranty_enabled',
        'warranty_text',
    ];

    protected $casts = [
        'received_date' => 'date',
        'estimated_date' => 'date',
        'delivered_date' => 'date',
        'warranty_enabled' => 'boolean',
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
        $totalAfterDiscount = (float) $this->total - (float) ($this->discount_amount ?? 0);

        return max(0, $totalAfterDiscount - (float) $this->advance_payment);
    }

    public function formattedReceivedTime(): ?string
    {
        return $this->received_time ? date('h:i A', strtotime($this->received_time)) : null;
    }

    public function formattedEstimatedDeliveryTime(): ?string
    {
        return $this->estimated_delivery_time ? date('h:i A', strtotime($this->estimated_delivery_time)) : null;
    }

    public function formattedDeliveredTime(): ?string
    {
        return $this->delivered_time ? date('h:i A', strtotime($this->delivered_time)) : null;
    }

    public function estimatedDeliveryDisplay(): string
    {
        if (! $this->estimated_date && ! $this->estimated_delivery_time) {
            return '—';
        }

        $parts = [];

        if ($this->estimated_date) {
            $parts[] = $this->estimated_date->format('d/m/Y');
        }

        if ($this->estimated_delivery_time) {
            $parts[] = $this->formattedEstimatedDeliveryTime();
        }

        return implode(' · ', $parts);
    }

    public function isEstimatedDeliveryOverdue(): bool
    {
        if (! $this->estimated_date || in_array($this->status, ['delivered', 'cancelled'], true)) {
            return false;
        }

        $deadline = $this->estimated_date->copy();

        if ($this->estimated_delivery_time) {
            [$hours, $minutes] = array_pad(explode(':', $this->estimated_delivery_time), 2, 0);

            $deadline->setTime((int) $hours, (int) $minutes);
        } else {
            $deadline->endOfDay();
        }

        return now()->greaterThan($deadline);
    }

    public function isEstimatedDeliveryToday(): bool
    {
        if (! $this->estimated_date || in_array($this->status, ['delivered', 'cancelled'], true)) {
            return false;
        }

        return $this->estimated_date->isToday();
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Pagado',
            'partial' => 'Parcial',
            'pending' => 'Pendiente',
            default => ucfirst((string) $this->payment_status),
        };
    }

    public function effectiveWarrantyText(): string
    {
        $custom = trim((string) $this->warranty_text);
        if ($custom !== '') {
            return $custom;
        }

        return app(CompanySettingsService::class)->get()['repair_warranty_text']
            ?: CompanySettingsService::DEFAULT_REPAIR_WARRANTY;
    }
}
