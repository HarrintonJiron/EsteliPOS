<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'position', 'salary', 'phone', 'address'];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}
