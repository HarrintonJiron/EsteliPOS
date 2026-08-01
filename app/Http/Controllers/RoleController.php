<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloneRoleRequest;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly RoleManagementService $roles) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);
        $query = Role::query()->with('permissions')->withCount('users');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if ($request->filled('type')) {
            $query->where('is_system', $request->query('type') === 'system');
        }

        return view('settings.roles.index', [
            'roles' => $query->orderByDesc('is_system')->orderBy('name')->paginate(15)->withQueryString(),
            'counts' => ['total' => Role::count(), 'system' => Role::where('is_system', true)->count(), 'custom' => Role::where('is_system', false)->count()],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);
        return view('settings.roles.create', $this->formData());
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roles->create($request->validated());
        return redirect()->route('settings.roles.show', $role)->with('success', 'Rol creado exitosamente.');
    }

    public function show(Role $role): View
    {
        $this->authorize('view', $role);
        $role->load('permissions', 'users')->loadCount('users');
        return view('settings.roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);
        $role->load('permissions');
        return view('settings.roles.edit', ['role' => $role, ...$this->formData()]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);
        $this->roles->update($role, $request->validated());
        return redirect()->route('settings.roles.show', $role)->with('success', 'Rol y matriz de permisos actualizados.');
    }

    public function cloneForm(Role $role): View
    {
        $this->authorize('create', Role::class);
        $role->load('permissions');
        return view('settings.roles.clone', compact('role'));
    }

    public function clone(CloneRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('create', Role::class);
        $clone = $this->roles->clone($role, $request->validated());
        return redirect()->route('settings.roles.edit', $clone)->with('success', 'Rol clonado. Revisa su matriz antes de usarlo.');
    }

    public function compare(Request $request): View
    {
        $this->authorize('viewAny', Role::class);
        $selected = collect($request->query('roles', []))->map(fn ($id) => (int) $id)->unique()->take(4);
        $roles = Role::query()->with('permissions')->whereIn('id', $selected)->orderBy('name')->get();

        return view('settings.roles.compare', [
            'roles' => $roles,
            'availableRoles' => Role::orderBy('name')->get(),
            'permissionsByModule' => Permission::orderBy('module')->orderBy('action')->get()->groupBy('module'),
        ]);
    }

    public function destroy(DeleteRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        try {
            $this->roles->delete($role, $request->integer('replacement_role_id') ?: null);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        return redirect()->route('settings.roles')->with('success', 'Rol eliminado y usuarios reasignados correctamente.');
    }

    public function deleteForm(Role $role): View
    {
        $this->authorize('delete', $role);
        $role->load('users');

        return view('settings.roles.delete', [
            'role' => $role,
            'replacementRoles' => Role::whereKeyNot($role->id)->orderBy('name')->get(),
        ]);
    }

    private function formData(): array
    {
        return ['permissionsByModule' => Permission::orderBy('module')->orderBy('action')->get()->groupBy('module')];
    }
}
