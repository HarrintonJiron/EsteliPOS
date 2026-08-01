<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'number', 'date', 'concept', 'reference', 'status',
        'total_debit', 'total_credit', 'source_type', 'source_id',
        'user_id', 'posted_at', 'voided_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeVoided($query)
    {
        return $query->where('status', self::STATUS_VOIDED);
    }

    public function isBalanced(): bool
    {
        return round((float) $this->lines()->sum('debit'), 2) === round((float) $this->lines()->sum('credit'), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_POSTED => 'Contabilizado',
            self::STATUS_VOIDED => 'Anulado',
            default => $this->status,
        };
    }
}
