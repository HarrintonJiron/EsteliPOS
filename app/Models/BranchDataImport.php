<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchDataImport extends Model
{
    protected $fillable = [
        'batch_id',
        'branch_id',
        'mode',
        'products_sha256',
        'receivables_sha256',
        'status',
        'summary',
        'applied_by',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'applied_at' => 'datetime',
        ];
    }
}
