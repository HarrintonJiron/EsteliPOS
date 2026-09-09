<?php

namespace App\Console\Commands;

use App\Models\NumberSequence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductionCertifyCommand extends Command
{
    protected $signature = 'app:production-certify {--json : Devuelve el resultado en JSON} {--allow-non-production : Permite certificar un entorno de pruebas}';

    protected $description = 'Certifica configuración, base de datos, migraciones, integridad, almacenamiento y tickets';

    public function handle(): int
    {
        $checks = [
            $this->check('environment', 'Entorno de producción', fn () => $this->option('allow-non-production') || app()->environment('production'), app()->environment()),
            $this->check('debug', 'Depuración desactivada', fn () => config('app.debug') === false, config('app.debug') ? 'activada' : 'desactivada'),
            $this->check('database', 'Conexión transaccional de base', function () {
                DB::transaction(function () {
                    DB::select('SELECT 1');
                });

                return true;
            }),
            $this->check('migrations', 'Migraciones al día', fn () => $this->pendingMigrations() === [], implode(', ', $this->pendingMigrations())),
            $this->check('integrity', 'Integridad operativa y contable', fn () => Artisan::call('app:check-integrity', ['--json' => true]) === self::SUCCESS),
            $this->check('sequences', 'Secuencias documentales activas y únicas', fn () => $this->sequencesAreReady()),
            $this->check('storage', 'Directorios de operación escribibles', fn () => $this->storageIsWritable()),
            $this->check('disk', 'Espacio libre mínimo de 500 MB', fn () => (disk_free_space(storage_path()) ?: 0) >= 500 * 1024 * 1024),
            $this->check('tickets', 'Tickets disponibles sin dependencias externas', fn () => $this->ticketsAreOffline()),
        ];

        $failures = collect($checks)->where('status', 'ERROR')->count();
        $result = ['status' => $failures === 0 ? 'APROBADO' : 'BLOQUEADO', 'failures' => $failures, 'checks' => $checks];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Control', 'Estado', 'Detalle'], collect($checks)->map(fn (array $check) => [$check['label'], $check['status'], $check['detail']])->all());
            $failures === 0
                ? $this->info('[APROBADO] La aplicación superó la certificación interna.')
                : $this->error("[BLOQUEADO] {$failures} controles requieren corrección.");
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $key, string $label, callable $condition, string $detail = ''): array
    {
        try {
            $passed = (bool) $condition();
        } catch (\Throwable $exception) {
            $passed = false;
            $detail = $exception->getMessage();
        }

        return ['key' => $key, 'label' => $label, 'status' => $passed ? 'OK' : 'ERROR', 'detail' => $detail];
    }

    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        if (! $migrator->repositoryExists()) {
            return ['tabla migrations inexistente'];
        }

        return array_values(array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $migrator->getRepository()->getRan()));
    }

    private function sequencesAreReady(): bool
    {
        $required = ['factura', 'compra', 'cotizacion', 'proforma', 'reparacion', 'recibo', 'ajuste', 'asiento'];

        return NumberSequence::query()->where('is_active', true)->whereIn('type', $required)->distinct()->count('type') === count($required);
    }

    private function storageIsWritable(): bool
    {
        foreach ([storage_path('app'), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $path) {
            if (! File::isDirectory($path) || ! is_writable($path)) {
                return false;
            }
        }

        return true;
    }

    private function ticketsAreOffline(): bool
    {
        foreach (['facturacion/receipt.blade.php', 'reparaciones/ticket.blade.php', 'proformas/ticket.blade.php'] as $view) {
            $contents = File::get(resource_path('views/'.$view));
            if (preg_match('/(?:src|href)=["\']https?:\/\//i', $contents)) {
                return false;
            }
        }

        return true;
    }
}
