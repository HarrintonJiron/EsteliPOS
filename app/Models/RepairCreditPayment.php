<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairCreditPayment extends Model
{
    protected $fillable = ['repair_order_id', 'client_id', 'user_id', 'amount', 'payment_date', 'payment_type', 'reference_number', 'notes', 'request_token'];

    protected $casts = ['amount' => 'decimal:2', 'payment_date' => 'datetime'];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
