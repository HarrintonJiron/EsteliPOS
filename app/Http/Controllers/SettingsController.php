<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'roles' => Role::count(),
            'active_modules' => Module::active()->count(),
            'total_modules' => Module::count(),
        ];

        $recentActivity = \App\Models\AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('settings.index', compact('stats', 'recentActivity'));
    }

    public function users()
    {
        $users = User::with('roles')->paginate(20);
        $roles = Role::all();

        return view('settings.users.index', compact('users', 'roles'));
    }

    public function roles()
    {
        $roles = Role::with('permissions')->withCount('users')->paginate(20);

        return view('settings.roles.index', compact('roles'));
    }

    public function permissions()
    {
        $permissions = \App\Models\Permission::orderBy('module')->orderBy('action')->get();
        $groupedPermissions = $permissions->groupBy('module');

        return view('settings.permissions.index', compact('groupedPermissions'));
    }

    public function general(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'company_address' => 'nullable|string',
                'company_phone' => 'nullable|string',
                'company_email' => 'nullable|email',
                'company_ruc' => 'nullable|string',
                'currency' => 'required|string|max:10',
                'tax_rate' => 'required|numeric|min:0|max:100',
                'timezone' => 'required|string',
            ]);

            foreach ($validated as $key => $value) {
                Setting::set($key, $value, 'string', 'general');
            }

            return redirect()->route('settings.general')->with('success', 'Configuración guardada exitosamente.');
        }

        $settings = Setting::getByGroup('general');

        return view('settings.general', compact('settings'));
    }

    public function modules(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'modules' => 'required|array',
                'modules.*.is_active' => 'boolean',
                'modules.*.sort_order' => 'integer',
            ]);

            // Obtener todos los módulos
            $allModules = Module::all();

            foreach ($allModules as $module) {
                $moduleId = $module->id;

                // Si el módulo está en el request, actualizar con los datos enviados
                if (isset($validated['modules'][$moduleId])) {
                    $data = $validated['modules'][$moduleId];
                    $module->update([
                        'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : false,
                        'sort_order' => $data['sort_order'] ?? $module->sort_order,
                    ]);
                } else {
                    // Si el módulo no está en el request, desactivarlo
                    $module->update(['is_active' => false]);
                }
            }

            return redirect()->route('settings.modules')->with('success', 'Módulos actualizados exitosamente.');
        }

        $modules = Module::ordered()->get();

        return view('settings.modules', compact('modules'));
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

            foreach ($validated as $key => $value) {
                Setting::set($key, $value, is_bool($value) ? 'boolean' : 'integer', 'security');
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
                'primary_color' => 'required|string|max:20',
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
