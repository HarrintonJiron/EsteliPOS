<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    protected $fillable = [
        'proforma_number',
        'client_id',
        'user_id',
        'client_name',
        'client_phone',
        'client_email',
        'client_address',
        'date',
        'expiry_date',
        'subtotal',
        'tax_total',
        'total',
        'tax_rate',
        'tax_included',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'expiry_date' => 'date',
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
        return $this->hasMany(ProformaDetail::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'    => 'Borrador',
            'sent'     => 'Enviada',
            'accepted' => 'Aceptada',
            'rejected' => 'Rechazada',
            'expired'  => 'Expirada',
            default    => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'    => 'bg-slate-100 text-slate-700',
            'sent'     => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
            'expired'  => 'bg-amber-100 text-amber-700',
            default    => 'bg-slate-100 text-slate-700',
        };
    }
}
