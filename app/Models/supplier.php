<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'business_name',
        'ruc',
        'contact_name',
        'phone',
        'email',
        'city',
        'address',
        'type',
        'payment_condition',
        'credit_limit',
        'status',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function getTotalPurchases(): float
    {
        return $this->purchases()
            ->where('status', 'completed')
            ->sum('total');
    }

    public function getPendingPurchases(): float
    {
        return $this->purchases()
            ->where('status', 'pending')
            ->sum('total');
    }

    public function getCreditUsed(): float
    {
        return $this->getPendingPurchases();
    }

    public function getCreditAvailable(): float
    {
        $limit = $this->credit_limit ?? 0;
        return max(0, $limit - $this->getCreditUsed());
    }

    public function hasCreditAvailable(): bool
    {
        return $this->getCreditAvailable() > 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            default => $this->status ?? 'Activo',
        };
    }

    public function getPaymentConditionLabelAttribute(): string
    {
        return match($this->payment_condition) {
            'contado' => 'Contado',
            'credito_15' => 'Crédito 15 días',
            'credito_30' => 'Crédito 30 días',
            'credito_60' => 'Crédito 60 días',
            default => $this->payment_condition ?? '—',
        };
    }
}
