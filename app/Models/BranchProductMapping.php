<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProductMapping extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'legacy_code', 'source_name'];
}
