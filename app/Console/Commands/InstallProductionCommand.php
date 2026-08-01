<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

class InstallProductionCommand extends Command
{
    protected $signature = 'app:install-production
        {--admin-name= : Nombre del administrador inicial}
        {--admin-email= : Correo del administrador inicial}
        {--force : Confirma la ejecución no interactiva en producción}';

    protected $description = 'Instala la estructura y catálogos esenciales sin cargar datos de demostración';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En producción debes confirmar explícitamente con --force.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('admin-name') ?: $this->ask('Nombre del administrador', 'Administrador')));
        $email = mb_strtolower(trim((string) ($this->option('admin-email') ?: $this->ask('Correo del administrador'))));
        $password = (string) (env('INSTALL_ADMIN_PASSWORD') ?: $this->secret('Contraseña inicial segura (12+ caracteres)'));

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->runStep('Ejecutando migraciones', 'migrate', ['--force' => true])) {
            return self::FAILURE;
        }

        if (! $this->runStep('Instalando catálogos de producción', 'db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ])) {
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password): void {
                $role = Role::where('slug', 'admin')->firstOrFail();
                $user = User::where('email', $email)->first();

                if (! $user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'role' => 'admin',
                        'password' => Hash::make($password),
                        'is_active' => true,
                        'force_password_change' => true,
                    ]);
                }

                $user->roles()->syncWithoutDetaching([$role->id]);
            });
        } catch (Throwable $exception) {
            $this->error('No se pudo crear el administrador: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! is_link(public_path('storage'))) {
            if ($this->callSilent('storage:link') !== self::SUCCESS) {
                $this->error('No se pudo crear el enlace público de archivos.');

                return self::FAILURE;
            }
        }

        $this->callSilent('optimize:clear');
        if ($this->callSilent('optimize') !== self::SUCCESS) {
            $this->error('La instalación terminó, pero no se pudo generar la caché de producción.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Instalación base completada sin productos, clientes, ventas ni compras de demostración.');
        $this->warn('El administrador deberá cambiar la contraseña en su primer ingreso.');
        $this->warn('Elimina INSTALL_ADMIN_PASSWORD del entorno si fue utilizado.');

        return self::SUCCESS;
    }

    private function runStep(string $label, string $command, array $arguments): bool
    {
        $successful = false;
        $this->components->task($label, function () use ($command, $arguments, &$successful) {
            $successful = $this->callSilent($command, $arguments) === self::SUCCESS;

            return $successful;
        });

        if (! $successful) {
            $this->error("Falló el paso: {$label}. Revisa el registro antes de continuar.");
        }

        return $successful;
    }
}
