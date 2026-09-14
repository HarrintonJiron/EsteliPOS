<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $nextProforma = max(
            $this->nextNumber('proformas', 'proforma_number', '/^PRO-([0-9]+)$/'),
            (int) DB::table('number_sequences')->where('type', 'proforma')->value('current_number'),
        );
        $nextRepair = max(
            $this->nextNumber('repair_orders', 'order_number', '/^REP-([0-9]+)$/'),
            (int) DB::table('number_sequences')->where('type', 'reparacion')->value('current_number'),
        );

        DB::table('number_sequences')->updateOrInsert(['type' => 'proforma'], [
            'prefix' => 'PRO-',
            'current_number' => $nextProforma,
            'padding' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('number_sequences')->updateOrInsert(['type' => 'reparacion'], [
            'prefix' => 'REP-',
            'current_number' => $nextRepair,
            'padding' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextNumber(string $table, string $column, string $pattern): int
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->pluck($column)
            ->reduce(function (int $max, mixed $value) use ($pattern): int {
                return is_string($value) && preg_match($pattern, $value, $matches) === 1
                    ? max($max, (int) $matches[1])
                    : $max;
            }, 0) + 1;
    }

    public function down(): void
    {
        DB::table('number_sequences')->whereIn('type', ['proforma', 'reparacion'])->delete();
    }
};
