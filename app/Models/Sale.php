<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'request_token',
        'client_id',
        'user_id',
        'branch_id',
        'caja_session_id',
        'warehouse_id',
        'price_list_id',
        'price_list_name',
        'billing_name',
        'billing_business_name',
        'billing_document_type',
        'billing_ruc',
        'billing_phone',
        'billing_email',
        'billing_address',
        'date',
        'due_date',
        'subtotal',
        'tax_total',
        'discount_amount',
        'discount_percentage',
        'total',
        'payment_type',
        'amount_paid',
        'change_amount',
        'tax_included',
        'tax_rate',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
        'due_date' => 'date',
        'tax_included' => 'boolean',
        'tax_rate' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cajaSession()
    {
        return $this->belongsTo(CajaSession::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function getBillingDocumentLabelAttribute(): string
    {
        return match ($this->billing_document_type) {
            'cedula' => 'Cédula',
            'ruc' => 'RUC',
            default => $this->client?->document_label ?? 'Documento',
        };
    }

    public function getBillingDocumentNumberAttribute(): ?string
    {
        return $this->billing_ruc ?: $this->client?->document_number;
    }
}
