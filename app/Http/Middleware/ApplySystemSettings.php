<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        self::apply();

        return $next($request);
    }

    public static function apply(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $timezone = Setting::get('timezone', config('app.timezone'));
        $language = Setting::get('language', config('app.locale'));
        $sessionTimeout = max(5, min(1440, (int) Setting::get('session_timeout', config('session.lifetime', 120))));

        config(['session.lifetime' => $sessionTimeout]);

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        if (is_string($language) && in_array($language, ['es', 'en'], true)) {
            app()->setLocale($language);
        }
    }
}
