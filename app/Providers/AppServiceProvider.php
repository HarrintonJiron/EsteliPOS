<?php

namespace App\Providers;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Policies\FiscalPeriodPolicy;
use App\Policies\JournalEntryPolicy;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Models\Module;
use App\Policies\ModulePolicy;
use App\Services\ModuleAccessService;
use App\Services\CompanySettingsService;
use App\Services\InvoiceTaxDisplayService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(FiscalPeriod::class, FiscalPeriodPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);

        View::composer([
            'layouts.app',
            'facturacion.print',
            'facturacion.pdf',
            'facturacion.receipt',
            'facturacion.show',
            'facturacion.change',
            'proformas.pdf',
            'proformas.ticket',
            'proformas.show',
            'compras.show',
            'contabilidad.impuestos.index',
            'auth.login',
            'auth.change-password',
        ], function ($view): void {
            $view->with('companyProfile', app(CompanySettingsService::class)->get());
            $view->with('invoiceTaxDisplay', app(InvoiceTaxDisplayService::class));
            if ($view->name() === 'layouts.app') {
                $view->with('accessibleModuleSlugs', app(ModuleAccessService::class)->accessibleSlugs(auth()->user()));
            }
        });
    }
}
