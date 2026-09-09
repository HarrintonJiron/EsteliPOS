<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('document_number', 30)->nullable()->unique()->after('id');
        });

        DB::table('purchases')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $purchase): void {
                DB::table('purchases')->where('id', $purchase->id)->update([
                    'document_number' => 'COM-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT),
                ]);
            });

        $nextNumber = ((int) DB::table('purchases')->max('id')) + 1;
        $currentNumber = (int) DB::table('number_sequences')->where('type', 'compra')->value('current_number');
        if (DB::table('number_sequences')->where('type', 'compra')->exists()) {
            DB::table('number_sequences')->where('type', 'compra')->update([
                'current_number' => max(1, $nextNumber, $currentNumber),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('number_sequences')->insert([
                'type' => 'compra',
                'prefix' => 'COM-',
                'current_number' => max(1, $nextNumber),
                'padding' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['document_number']);
            $table->dropColumn('document_number');
        });
    }
};
