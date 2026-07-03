<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'user_id',
        'billing_name',
        'billing_business_name',
        'billing_ruc',
        'billing_phone',
        'billing_email',
        'billing_address',
        'date',
        'due_date',
        'subtotal',
        'tax_total',
        'total',
        'payment_type',
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

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
