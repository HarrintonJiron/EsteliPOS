<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchProductSource extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'source_code',
        'source_description',
        'source_stock',
        'source_cost',
        'sale_price_1',
        'sale_price_2',
        'sale_price_3',
        'sale_price_4',
        'tax_percent',
        'quantity_2',
        'quantity_3',
        'quantity_4',
        'automatic_price',
        'quick_code',
        'barcode',
        'unit_name',
        'location',
        'category_name',
        'brand',
        'model',
        'product_line',
        'expiration_date',
        'minimum_stock',
        'control_stock',
        'show_in_sales',
        'is_wood',
        'board_feet',
        'remote_print',
        'source_file',
        'source_row',
        'raw_data',
        'stock_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'automatic_price' => 'boolean',
            'control_stock' => 'boolean',
            'show_in_sales' => 'boolean',
            'is_wood' => 'boolean',
            'remote_print' => 'boolean',
            'expiration_date' => 'date:Y-m-d',
            'raw_data' => 'array',
            'stock_imported_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
