<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arqueo extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 'user_id', 'total_sales_count', 'total_sales_amount', 'cash_total', 'credit_payments_total', 'physical_total', 'difference', 'details'
    ];

    protected $casts = [
        'date' => 'date',
        'details' => 'array',
        'total_sales_amount' => 'decimal:2',
        'cash_total' => 'decimal:2',
        'credit_payments_total' => 'decimal:2',
        'physical_total' => 'decimal:2',
        'difference' => 'decimal:2',
    ];
}
