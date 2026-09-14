<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('journal_entries')
            ->select('source_type', 'source_id')
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->whereIn('status', ['draft', 'posted'])
            ->groupBy('source_type', 'source_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                "Existen asientos activos duplicados para {$duplicate->source_type} #{$duplicate->source_id}. Corríjalos antes de actualizar."
            );
        }

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->string('active_source_key', 191)->nullable()->unique('journal_entries_active_source_unique');
        });

        DB::table('journal_entries')
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->whereIn('status', ['draft', 'posted'])
            ->orderBy('id')
            ->eachById(function (object $entry): void {
                DB::table('journal_entries')->where('id', $entry->id)->update([
                    'active_source_key' => $entry->source_type.':'.$entry->source_id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropUnique('journal_entries_active_source_unique');
            $table->dropColumn('active_source_key');
        });
    }
};
