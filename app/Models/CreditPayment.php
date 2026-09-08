<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    use HasFactory;

    protected $table = 'credit_payments';

    protected $fillable = [
        'client_id',
        'sale_id',
        'amount',
        'payment_date',
        'payment_type',
        'reference_number',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    // Relaciones
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
