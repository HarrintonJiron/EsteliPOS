<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class MigrateProductionCommand extends Command
{
    protected $signature = 'app:migrate-production
                            {--force : Required for production environments}';

    protected $description = 'Run pending migrations one by one; skip create conflicts when tables already exist (safe for upgrades from older installs)';

    public function handle(): int
    {
        if (! $this->option('force') && $this->laravel->environment('production')) {
            $this->error('Use --force to run migrations in production.');

            return self::FAILURE;
        }

        $migrationFiles = collect(File::files(database_path('migrations')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.php'))
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        $ran = DB::table('migrations')->pluck('migration')->all();
        $pending = $migrationFiles
            ->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->reject(fn (string $name) => in_array($name, $ran, true))
            ->values();

        if ($pending->isEmpty()) {
            $this->info('Nothing to migrate.');

            return self::SUCCESS;
        }

        $this->info('Pending migrations: '.$pending->count());

        foreach ($pending as $migration) {
            $relativePath = 'database/migrations/'.$migration.'.php';
            $this->line("→ {$migration}");

            try {
                $exitCode = Artisan::call('migrate', [
                    '--path' => $relativePath,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                $output = trim(Artisan::output());

                if ($exitCode === 0) {
                    if ($output !== '') {
                        $this->line($output);
                    }

                    continue;
                }

                if ($this->isAlreadyExistsError($output)) {
                    $this->warn("  Ya existe en la base; registrando migracion: {$migration}");
                    $this->markMigrationAsRan($migration);

                    continue;
                }

                $this->error($output !== '' ? $output : "La migracion {$migration} fallo.");

                return self::FAILURE;
            } catch (Throwable $exception) {
                if (! $this->isAlreadyExistsError($exception->getMessage())) {
                    $this->error($exception->getMessage());

                    return self::FAILURE;
                }

                $this->warn("  Ya existe en la base; registrando migracion: {$migration}");
                $this->markMigrationAsRan($migration);
            }
        }

        $this->info('Migraciones de produccion completadas.');

        return self::SUCCESS;
    }

    private function isAlreadyExistsError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'already exists')
            || str_contains($normalized, 'duplicate column')
            || str_contains($normalized, 'duplicate column name');
    }

    private function markMigrationAsRan(string $migration): void
    {
        if (DB::table('migrations')->where('migration', $migration)->exists()) {
            return;
        }

        $batch = (int) DB::table('migrations')->max('batch');
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => max(1, $batch + 1),
        ]);
    }
}
