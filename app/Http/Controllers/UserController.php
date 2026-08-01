<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use App\Support\PasswordPolicy;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($roles) => $roles->where('slug', $request->query('role')));
        }

        return view('settings.users.index', [
            'users' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'roles' => $this->roles(),
            'counts' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('settings.users.create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        return redirect()->route('settings.users.show', $user)->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user): View
    {
        $user->load('roles.permissions', 'directPermissions');
        $activity = AuditLog::query()
            ->with('user')
            ->where(function ($query) use ($user) {
                $query->where(fn ($subject) => $subject->where('model_type', User::class)->where('model_id', $user->id))
                    ->orWhere('user_id', $user->id);
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('settings.users.show', compact('user', 'activity'));
    }

    public function edit(User $user): View
    {
        $user->load('roles', 'directPermissions');

        return view('settings.users.edit', ['user' => $user, ...$this->formData()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->users->update($user, $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.users.show', $user)->with('success', 'Usuario actualizado exitosamente.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        try {
            $this->users->toggleActive($user, request()->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Usuario '.($user->is_active ? 'activado' : 'desactivado').' exitosamente.');
    }

    public function resetPasswordForm(User $user): View
    {
        return view('settings.users.reset-password', [
            'user' => $user,
            'passwordPolicy' => PasswordPolicy::summary(),
        ]);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->users->resetPassword($user, $request->validated('password'), $request->boolean('force_password_change'));

        return redirect()->route('settings.users.show', $user)->with('success', 'Contraseña restablecida de forma segura. No se almacenó ni mostró en texto plano.');
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->users->delete($user, request()->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('settings.users')->with('success', 'Usuario eliminado exitosamente.');
    }

    private function formData(): array
    {
        return [
            'roles' => $this->roles(),
            'permissionsByModule' => Permission::query()->orderBy('module')->orderBy('name')->get()->groupBy('module'),
            'passwordPolicy' => PasswordPolicy::summary(),
        ];
    }

    private function roles()
    {
        return Role::query()->orderByDesc('is_system')->orderBy('name')->get()->unique('slug')->values();
    }
}
