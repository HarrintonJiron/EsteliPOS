<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'image_url',
        'discount_pct',
        'discount_label',
        'tax_id',
    ];

    public function effectivePrice(): float
    {
        if ((float) $this->discount_pct > 0) {
            return round((float) $this->sale_price * (1 - (float) $this->discount_pct / 100), 2);
        }

        return (float) $this->sale_price;
    }

    public function getImageUrlAttribute(?string $value): ?string
    {
        if (! $value || str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    public function effectiveTaxRate(): float
    {
        if ($this->tax_id && $this->tax?->is_active) {
            return (float) $this->tax->rate;
        }

        return Tax::defaultRate();
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

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

    public function calculatedStock(): int
    {
        $in = (int) $this->inventoryMovements()->where('type', 'in')->sum('quantity');
        $out = (int) $this->inventoryMovements()->where('type', 'out')->sum('quantity');

        return $in - $out;
    }

    public function hasStockDiscrepancy(): bool
    {
        return $this->stock !== $this->calculatedStock();
    }

    public function rotationIndex(int $soldQty): float
    {
        $base = max($this->stock, 1);

        return round($soldQty / $base, 2);
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
        if (! $this->expiry_date) {
            return false;
        }

        return \Carbon\Carbon::parse($this->expiry_date)->isPast();
    }

    public function expiresSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return \Carbon\Carbon::parse($this->expiry_date)->diffInDays(now()) <= $days;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'discontinued' => 'Descontinuado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
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

    public function suppliers()
    {
        return $this->belongsToMany(
            Supplier::class,
            'product_supplier'
        )
            ->withPivot(
                'purchase_price',
                'supplier_code',
                'preferred'
            )
            ->withTimestamps();
    }

    public function getInventoryStatusLabelAttribute(): string
    {
        return match ($this->inventory_status) {
            'expired' => 'Vencido',
            'expiring_soon' => 'Por Vencer',
            'low_stock' => 'Bajo Stock',
            'normal' => 'Stock Normal',
            default => 'Desconocido',
        };
    }
}
