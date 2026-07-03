<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'description',
        'purchase_price',
        'sale_price',
        'stock',
        'unit',
        'lot',
        'expiry_date',
        'location',
        'low_stock_threshold',
        'registration_number',
        'active_ingredient',
        'concentration',
        'status',
        'observations',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryAdjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= ($this->low_stock_threshold ?? 10);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return \Carbon\Carbon::parse($this->expiry_date)->isPast();
    }

    public function expiresSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return \Carbon\Carbon::parse($this->expiry_date)->diffInDays(now()) <= $days;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'discontinued' => 'Descontinuado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'discontinued' => 'red',
            default => 'gray',
        };
    }

    public function getInventoryStatusAttribute(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->expiresSoon(30)) {
            return 'expiring_soon';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'normal';
    }

    public function getInventoryStatusLabelAttribute(): string
    {
        return match($this->inventory_status) {
            'expired' => 'Vencido',
            'expiring_soon' => 'Por Vencer',
            'low_stock' => 'Bajo Stock',
            'normal' => 'Stock Normal',
            default => 'Desconocido',
        };
    }
}
