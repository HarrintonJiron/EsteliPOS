<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = Setting::get('timezone', config('app.timezone'));
        $language = Setting::get('language', config('app.locale'));

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        if (is_string($language) && in_array($language, ['es', 'en'], true)) {
            app()->setLocale($language);
        }

        return $next($request);
    }
}
