<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('configuracion.manage_permissions'), 403);

        return view('settings.permissions.index', [
            'roles' => Role::query()->with('permissions')->orderByDesc('is_system')->orderBy('name')->get(),
            'permissionsByModule' => Permission::query()->orderBy('module')->orderBy('action')->get()->groupBy('module'),
        ]);
    }
}
