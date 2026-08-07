<?php

namespace App\Providers;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Policies\FiscalPeriodPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\ModulePolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\BackNavigationService;
use App\Services\CompanySettingsService;
use App\Services\InvoiceTaxDisplayService;
use App\Services\ModuleAccessService;
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

        View::composer('*', function ($view): void {
            if (! array_key_exists('companyProfile', $view->getData())) {
                $view->with('companyProfile', app(CompanySettingsService::class)->get());
            }

            if (! array_key_exists('invoiceTaxDisplay', $view->getData())) {
                $view->with('invoiceTaxDisplay', app(InvoiceTaxDisplayService::class));
            }
        });

        View::composer('layouts.app', function ($view): void {
            $view->with('accessibleModuleSlugs', app(ModuleAccessService::class)->accessibleSlugs(auth()->user()));

            if (! array_key_exists('backNavigation', $view->getData())) {
                $view->with('backNavigation', app(BackNavigationService::class)->resolve());
            }
        });
    }
}
