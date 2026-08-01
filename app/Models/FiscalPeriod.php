<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalPeriod extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_ANNUAL = 'annual';

    protected $fillable = [
        'type', 'year', 'month', 'start_date', 'end_date', 'status',
        'closed_at', 'closed_by', 'reopened_at', 'reopened_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeMonthly($query)
    {
        return $query->where('type', self::TYPE_MONTHLY);
    }

    public function scopeAnnual($query)
    {
        return $query->where('type', self::TYPE_ANNUAL);
    }

    /**
     * Find (or lazily create) the monthly period record covering a given date.
     */
    public static function forMonth(int $year, int $month): self
    {
        return static::firstOrCreate(
            ['type' => self::TYPE_MONTHLY, 'year' => $year, 'month' => $month],
            [
                'start_date' => now()->setDate($year, $month, 1)->startOfMonth()->toDateString(),
                'end_date' => now()->setDate($year, $month, 1)->endOfMonth()->toDateString(),
                'status' => self::STATUS_OPEN,
            ]
        );
    }

    /**
     * Find (or lazily create) the annual period record for a given year.
     */
    public static function forYear(int $year): self
    {
        return static::firstOrCreate(
            ['type' => self::TYPE_ANNUAL, 'year' => $year, 'month' => null],
            [
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'status' => self::STATUS_OPEN,
            ]
        );
    }

    /**
     * Whether a given date falls inside a closed monthly or annual period.
     */
    public static function isDateClosed(string $date): bool
    {
        return static::closed()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    public function close(User $user): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $user->id,
            'reopened_at' => null,
            'reopened_by' => null,
        ]);
    }

    public function reopen(User $user): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'reopened_at' => now(),
            'reopened_by' => $user->id,
        ]);
    }
}
