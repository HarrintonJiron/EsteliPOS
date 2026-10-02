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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        RateLimiter::for('system-reset', function (Request $request): Limit {
            $administrator = $request->user()?->getAuthIdentifier() ?? 'guest';

            return Limit::perMinute(5)->by($administrator.'|'.$request->ip());
        });

        Blade::directive('money', fn (string $expression): string => "<?php echo app(\\App\\Services\\MoneyDisplayService::class)->format({$expression}); ?>");

        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(FiscalPeriod::class, FiscalPeriodPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);

        View::composer('*', function ($view): void {
            $companyProfile = $view->getData()['companyProfile'] ?? app(CompanySettingsService::class)->get();
            $view->with('companyProfile', $companyProfile);
            $view->with('currencySymbol', $view->getData()['currencySymbol'] ?? ($companyProfile['currency_symbol'] ?? 'C$'));
            $view->with('companyCurrency', $view->getData()['companyCurrency'] ?? ($companyProfile['currency'] ?? 'NIO'));

            if (! array_key_exists('invoiceTaxDisplay', $view->getData())) {
                $view->with('invoiceTaxDisplay', app(InvoiceTaxDisplayService::class));
            }
        });

        View::composer('layouts.app', function ($view): void {
            $view->with('accessibleModuleSlugs', app(ModuleAccessService::class)->accessibleSlugs(auth()->user()));
            $view->with('quickSwitchUsers', Schema::hasColumn('users', 'pin_hash')
                ? User::query()
                    ->where('is_active', true)
                    ->whereNotNull('pin_hash')
                    ->orderBy('name')
                    ->get(['id', 'name', 'username', 'profile_photo'])
                : collect());

            if (! array_key_exists('backNavigation', $view->getData())) {
                $view->with('backNavigation', app(BackNavigationService::class)->resolve());
            }
        });
    }
}
