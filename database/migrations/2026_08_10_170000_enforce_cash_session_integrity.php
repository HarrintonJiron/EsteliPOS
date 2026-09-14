<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $openCount = DB::table('caja_sessions')->where('status', 'open')->count();
        if ($openCount > 1) {
            throw new RuntimeException(
                'La base de datos contiene más de una sesión de caja abierta. Cierre las sesiones duplicadas antes de actualizar.'
            );
        }

        Schema::table('caja_sessions', function (Blueprint $table): void {
            $table->string('open_guard', 10)->nullable()->unique('caja_sessions_open_guard_unique');
        });

        DB::table('caja_sessions')
            ->where('status', 'open')
            ->update(['open_guard' => 'OPEN']);

        $duplicateArqueo = DB::table('arqueos')
            ->select('caja_session_id')
            ->whereNotNull('caja_session_id')
            ->groupBy('caja_session_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('caja_session_id');

        if ($duplicateArqueo !== null) {
            throw new RuntimeException(
                "La base de datos contiene cierres duplicados en la sesión de caja {$duplicateArqueo}. Corríjalos antes de actualizar."
            );
        }

        Schema::table('arqueos', function (Blueprint $table): void {
            $table->unique('caja_session_id', 'arqueos_caja_session_unique');
        });
    }

    public function down(): void
    {
        Schema::table('arqueos', function (Blueprint $table): void {
            $table->dropUnique('arqueos_caja_session_unique');
        });
        Schema::table('caja_sessions', function (Blueprint $table): void {
            $table->dropUnique('caja_sessions_open_guard_unique');
            $table->dropColumn('open_guard');
        });
    }
};
