<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->paginate(20);

        return view('settings.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');

        return view('settings.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('settings.roles')->with('success', 'Rol creado exitosamente.');
    }

    public function show(Role $role)
    {
        $role->load('permissions', 'users');

        return view('settings.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');

        return view('settings.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'No se pueden modificar roles del sistema.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id.'|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('settings.roles')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'No se pueden eliminar roles del sistema.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados.');
        }

        $role->delete();

        return redirect()->route('settings.roles')->with('success', 'Rol eliminado exitosamente.');
    }
}
