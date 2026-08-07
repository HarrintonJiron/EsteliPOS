<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class BackNavigationService
{
    /**
     * @return array{href: string, label: string}|null
     */
    public function resolve(?string $routeName = null): ?array
    {
        $routeName ??= Route::currentRouteName();

        if ($routeName === null || $this->shouldSkip($routeName)) {
            return null;
        }

        if ($override = $this->resolveOverride($routeName)) {
            return $override;
        }

        if ($subIndexBack = $this->resolveSubIndexBack($routeName)) {
            return $subIndexBack;
        }

        if (preg_match('/^(.+)\.edit$/', $routeName, $matches)) {
            $showRoute = $matches[1].'.show';

            if (Route::has($showRoute) && ($params = $this->routeParameters()) !== []) {
                return $this->backTo($showRoute, $params);
            }
        }

        if (preg_match('/^(.+)\.(create|edit|show)$/', $routeName, $matches)) {
            $indexRoute = $matches[1].'.index';

            if (Route::has($indexRoute)) {
                return $this->backTo($indexRoute);
            }
        }

        $parentRoute = $this->parentRoute($routeName);

        if ($parentRoute !== null) {
            return $this->backTo($parentRoute);
        }

        return null;
    }

    private function shouldSkip(string $routeName): bool
    {
        if (in_array($routeName, $this->hubRoutes(), true)) {
            return true;
        }

        return in_array($routeName, $this->mainIndexRoutes(), true);
    }

    /**
     * @return list<string>
     */
    private function hubRoutes(): array
    {
        return [
            'login',
            'logout',
            'welcome',
            'dashboard',
            'dashboard.general',
            'password.change',
            'facturacion.pos',
            'facturacion.index',
            'proformas.pos',
            'contabilidad.dashboard',
            'settings.index',
            'planilla.index',
            'inventario.index',
        ];
    }

    /**
     * @return list<string>
     */
    private function mainIndexRoutes(): array
    {
        return [
            'facturacion.index',
            'creditos.index',
            'arqueo.index',
            'inventario.index',
            'proveedores.index',
            'compras.index',
            'clientes.index',
            'planilla.index',
            'proformas.index',
            'reparaciones.index',
            'reportes.index',
            'ajustes.index',
            'settings.index',
            'employees.index',
            'leave.index',
            'loans.index',
            'bonuses.index',
            'deductions.index',
            'movimientos.index',
            'device-brands.index',
            'reparaciones.gastos.index',
        ];
    }

    /**
     * @return array{href: string, label: string}|null
     */
    private function resolveSubIndexBack(string $routeName): ?array
    {
        if (str_starts_with($routeName, 'contabilidad.') && str_ends_with($routeName, '.index')) {
            return $this->backTo('contabilidad.dashboard');
        }

        if (in_array($routeName, [
            'inventario.warehouses.index',
            'inventario.price-lists.index',
            'inventario.units.index',
            'nomina.index',
        ], true)) {
            return $this->backTo(match ($routeName) {
                'nomina.index' => 'planilla.index',
                default => 'inventario.index',
            });
        }

        if ($routeName === 'settings.users' || $routeName === 'settings.roles' || $routeName === 'settings.permissions' || $routeName === 'settings.modules') {
            return $this->backTo('settings.index');
        }

        if (str_starts_with($routeName, 'settings.') && str_ends_with($routeName, '.index') && $routeName !== 'settings.index') {
            return $this->backTo('settings.index');
        }

        return null;
    }

    /**
     * @return array{href: string, label: string}|null
     */
    private function resolveOverride(string $routeName): ?array
    {
        $route = request()->route();

        $overrides = [
            'inventario.dashboard' => 'inventario.index',
            'inventario.quick' => 'inventario.index',
            'inventario.bulk' => 'inventario.index',
            'inventario.create' => 'inventario.index',
            'facturacion.create' => 'facturacion.pos',
            'facturacion.change' => 'facturacion.pos',
            'facturacion.receipt' => 'facturacion.pos',
            'facturacion.print' => 'facturacion.index',
            'facturacion.pdf' => 'facturacion.index',
            'proformas.pdf' => 'proformas.index',
            'proformas.ticket' => 'proformas.show',
            'reparaciones.ticket' => 'reparaciones.show',
            'reparaciones.pdf' => 'reparaciones.show',
            'movimientos.index' => 'inventario.index',
            'ajustes.create' => 'ajustes.index',
            'settings.general' => 'settings.index',
            'settings.appearance' => 'settings.index',
            'settings.security' => 'settings.index',
            'settings.sequences' => 'settings.index',
            'creditos.overdue' => 'creditos.index',
            'creditos.report' => 'creditos.index',
            'creditos.search' => 'creditos.index',
            'employees.create' => 'planilla.index',
            'employees.edit' => 'planilla.index',
            'employees.show' => 'planilla.index',
            'settings.roles.compare' => 'settings.roles',
            'settings.roles.clone.form' => 'settings.roles.show',
            'settings.roles.delete.form' => 'settings.roles.show',
            'settings.users.reset-password.form' => 'settings.users.show',
            'password.change' => 'dashboard.general',
        ];

        if (! array_key_exists($routeName, $overrides)) {
            if ($routeName === 'creditos.create' && $route?->parameter('clientId')) {
                return $this->backTo('creditos.show', ['clientId' => $route->parameter('clientId')]);
            }

            if ($routeName === 'creditos.statement' && $route?->parameter('clientId')) {
                return $this->backTo('creditos.show', ['clientId' => $route->parameter('clientId')]);
            }

            if ($routeName === 'creditos.invoice') {
                return $this->backTo('creditos.index');
            }

            return null;
        }

        $target = $overrides[$routeName];

        if ($routeName === 'proformas.ticket' || $routeName === 'reparaciones.ticket' || $routeName === 'reparaciones.pdf') {
            $params = $this->routeParameters();

            return $this->backTo($target, $params !== [] ? $params : []);
        }

        if ($routeName === 'settings.roles.clone.form' || $routeName === 'settings.roles.delete.form') {
            return $this->backTo($target, ['role' => $route?->parameter('role')]);
        }

        if ($routeName === 'settings.users.reset-password.form') {
            return $this->backTo($target, ['user' => $route?->parameter('user')]);
        }

        return $this->backTo($target);
    }

    private function parentRoute(string $routeName): ?string
    {
        $segments = explode('.', $routeName);
        array_pop($segments);

        while ($segments !== []) {
            $candidate = implode('.', $segments);

            if (Route::has($candidate)) {
                return $candidate;
            }

            array_pop($segments);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{href: string, label: string}
     */
    private function backTo(string $routeName, array $params = []): array
    {
        return [
            'href' => route($routeName, $params),
            'label' => 'Regresar',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(): array
    {
        $route = request()->route();

        if ($route === null) {
            return [];
        }

        foreach ([
            'id', 'warehouse', 'priceList', 'employee', 'loan', 'bonus', 'deduction',
            'leave', 'leaveRequest', 'operationalExpense', 'clientId', 'saleId',
            'journalEntry', 'account', 'centro_costo', 'tax', 'user', 'role', 'exchangeRate',
            'proforma', 'paymentId',
        ] as $key) {
            $value = $route->parameter($key);

            if ($value !== null) {
                return [$key => $value];
            }
        }

        return [];
    }
}
