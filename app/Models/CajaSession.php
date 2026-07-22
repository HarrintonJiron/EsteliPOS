<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaSession extends Model
{
    use HasFactory;

    protected $table = 'caja_sessions';

    protected $fillable = [
        'date', 'opened_at', 'opened_by', 'closed_at', 'closed_by', 'status'
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
