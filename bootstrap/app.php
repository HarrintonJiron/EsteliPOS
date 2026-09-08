<?php

use App\Console\Commands\ImportClientInventoryCommand;
use App\Console\Commands\InstallProductionCommand;
use App\Console\Commands\VerifyImagePipelineCommand;
use App\Http\Middleware\ApplySystemSettings;
use App\Http\Middleware\CheckModule;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Services\ModuleAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        InstallProductionCommand::class,
        ImportClientInventoryCommand::class,
        VerifyImagePipelineCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [ApplySystemSettings::class]);
        $middleware->web(append: [EnsurePasswordIsChanged::class]);

        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'module' => CheckModule::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $redirectForbiddenRequest = function (Request $request) {
            if ($request->expectsJson() || ! $request->user()) {
                return null;
            }

            $destination = app(ModuleAccessService::class)->defaultHomeUrl($request->user());
            if (rtrim($request->url(), '/') === rtrim($destination, '/')) {
                $destination = route('access.unavailable');
            }

            return redirect()->to($destination)->with(
                'error',
                'No tienes permisos para realizar esa acción. Solo se muestran las funciones habilitadas para tu usuario.'
            );
        };

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($redirectForbiddenRequest) {
            return $redirectForbiddenRequest($request);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($redirectForbiddenRequest) {
            if ($exception->getStatusCode() !== 403) {
                return null;
            }

            return $redirectForbiddenRequest($request);
        });
    })->create();
