<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Support\SettingDefinition;
use App\Services\SettingsDashboardService;
use App\Services\CompanySettingsService;
use App\Http\Requests\UpdateCompanySettingsRequest;

class SettingsController extends Controller
{
    public function index(SettingsDashboardService $dashboard)
    {
        return view('settings.index', $dashboard->build());
    }

    public function general(CompanySettingsService $companySettings)
    {
        $settings = $companySettings->get();

        return view('settings.general', compact('settings'));
    }

    public function updateGeneral(UpdateCompanySettingsRequest $request, CompanySettingsService $companySettings)
    {
        $companySettings->update(
            $request->validated(),
            $request->file('company_logo'),
            $request->file('ticket_logo'),
        );

        return redirect()->route('settings.general')->with('success', 'La información de empresa se guardó correctamente.');
    }

    public function security(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'session_timeout' => 'required|integer|min:5|max:1440',
                'max_login_attempts' => 'required|integer|min:1|max:10',
                'password_min_length' => 'required|integer|min:6|max:20',
                'password_require_uppercase' => 'boolean',
                'password_require_lowercase' => 'boolean',
                'password_require_numbers' => 'boolean',
                'password_require_special_chars' => 'boolean',
                'two_factor_enabled' => 'boolean',
            ]);

            $booleanKeys = [
                'password_require_uppercase',
                'password_require_lowercase',
                'password_require_numbers',
                'password_require_special_chars',
                'two_factor_enabled',
            ];

            foreach ($booleanKeys as $key) {
                $validated[$key] = $request->boolean($key);
            }

            foreach ($validated as $key => $value) {
                Setting::set($key, $value, SettingDefinition::typeFor($key), 'security');
            }

            return redirect()->route('settings.security')->with('success', 'Configuración de seguridad guardada exitosamente.');
        }

        $settings = Setting::getByGroup('security');
        return view('settings.security', compact('settings'));
    }

    public function appearance(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'theme' => 'required|in:light,dark,auto',
                'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'system_name' => 'required|string|max:100',
            ]);

            foreach ($validated as $key => $value) {
                Setting::set($key, $value, 'string', 'appearance');
            }

            return redirect()->route('settings.appearance')->with('success', 'Configuración de apariencia guardada exitosamente.');
        }

        $settings = Setting::getByGroup('appearance');
        return view('settings.appearance', compact('settings'));
    }

    public function sequences(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'sequences' => 'required|array',
                'sequences.*.prefix' => 'required|string|max:10',
                'sequences.*.current_number' => 'required|integer|min:1',
                'sequences.*.padding' => 'required|integer|min:1|max:10',
                'sequences.*.is_active' => 'boolean',
            ]);

            foreach ($validated['sequences'] as $type => $data) {
                $sequence = \App\Models\NumberSequence::byType($type)->first();
                if ($sequence) {
                    $sequence->update($data);
                }
            }

            return redirect()->route('settings.sequences')->with('success', 'Numeraciones actualizadas exitosamente.');
        }

        $sequences = \App\Models\NumberSequence::all()->keyBy('type');
        return view('settings.sequences', compact('sequences'));
    }
}
