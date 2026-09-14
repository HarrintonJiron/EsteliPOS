<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
     * Account types (naturaleza contable) grouped by main financial statement classification.
     */
    public const TYPES = [
        'asset_current' => 'Activo Corriente',
        'asset_non_current' => 'Activo No Corriente',
        'liability_current' => 'Pasivo Corriente',
        'liability_long_term' => 'Pasivo Largo Plazo',
        'equity' => 'Capital',
        'revenue' => 'Ingresos',
        'cost_of_sales' => 'Costos',
        'expense' => 'Gastos',
        'other_income' => 'Otros Ingresos',
        'other_expense' => 'Otros Gastos',
    ];

    /**
     * Maps each detailed type to its top-level classification (used by Balance General / Estado de Resultados).
     */
    public const MAIN_GROUPS = [
        'asset_current' => 'activo',
        'asset_non_current' => 'activo',
        'liability_current' => 'pasivo',
        'liability_long_term' => 'pasivo',
        'equity' => 'capital',
        'revenue' => 'ingresos',
        'cost_of_sales' => 'costos',
        'expense' => 'gastos',
        'other_income' => 'otros_ingresos',
        'other_expense' => 'otros_gastos',
    ];

    /**
     * The natural balance side per type (deudora = debit, acreedora = credit).
     */
    public const NATURE_BY_TYPE = [
        'asset_current' => 'debit',
        'asset_non_current' => 'debit',
        'liability_current' => 'credit',
        'liability_long_term' => 'credit',
        'equity' => 'credit',
        'revenue' => 'credit',
        'cost_of_sales' => 'debit',
        'expense' => 'debit',
        'other_income' => 'credit',
        'other_expense' => 'debit',
    ];

    protected $fillable = [
        'code', 'name', 'description', 'type', 'nature',
        'parent_id', 'level', 'is_postable', 'is_system', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Account $account) {
            $account->level = $account->parent_id
                ? (static::find($account->parent_id)?->level ?? 0) + 1
                : 1;
        });
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfMainGroup($query, string $mainGroup)
    {
        $types = array_keys(array_filter(self::MAIN_GROUPS, fn ($group) => $group === $mainGroup));

        return $query->whereIn('type', $types);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getMainGroupAttribute(): string
    {
        return self::MAIN_GROUPS[$this->type] ?? '';
    }

    public function getMainGroupLabelAttribute(): string
    {
        return match ($this->main_group) {
            'activo' => 'Activo',
            'pasivo' => 'Pasivo',
            'capital' => 'Capital',
            'ingresos' => 'Ingresos',
            'costos' => 'Costos',
            'gastos' => 'Gastos',
            'otros_ingresos' => 'Otros Ingresos',
            'otros_gastos' => 'Otros Gastos',
            default => '',
        };
    }

}
