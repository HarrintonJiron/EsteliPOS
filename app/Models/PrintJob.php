<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'dedup_key',
        'status',
        'requested_by',
        'station_user_id',
        'attempts',
        'claimed_at',
        'printed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'claimed_at' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }
}
