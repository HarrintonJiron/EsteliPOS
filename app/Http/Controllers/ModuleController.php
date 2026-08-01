<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateModulesRequest;
use App\Models\Module;
use App\Models\Role;
use App\Services\ModuleManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Module::class);
        $modules = Module::with('roles')->ordered()->get();

        return view('settings.modules', [
            'modules' => $modules,
            'roles' => Role::orderByDesc('is_system')->orderBy('name')->get(),
            'moduleNames' => $modules->pluck('name', 'slug'),
        ]);
    }

    public function update(UpdateModulesRequest $request, ModuleManagementService $service): RedirectResponse
    {
        $this->authorize('viewAny', Module::class);
        try {
            $service->update($request->validated('modules'));
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.modules')->with('success', 'Módulos, dependencias y accesos actualizados.');
    }
}
