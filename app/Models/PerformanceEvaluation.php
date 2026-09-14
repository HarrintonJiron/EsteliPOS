<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceEvaluation extends Model
{
    protected $fillable = ['employee_id', 'evaluation_date', 'score', 'period', 'strengths', 'improvements', 'comments', 'evaluator_id'];

    protected function casts(): array
    {
        return ['evaluation_date' => 'date', 'score' => 'integer'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->score >= 90 ? 'Excelente' : ($this->score >= 80 ? 'Satisfactorio' : ($this->score >= 70 ? 'En desarrollo' : 'Requiere seguimiento'));
    }
}
