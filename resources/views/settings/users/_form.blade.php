@php
    $editing = isset($user);
    $selectedRoles = collect(old('roles', $editing ? $user->roles->pluck('id')->all() : []))->map(fn($id) => (int) $id);
    $selectedPermissions = collect(old('permissions', $editing ? $user->directPermissions->pluck('id')->all() : []))->map(fn($id) => (int) $id);
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Datos del usuario</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-medium">Nombre completo *</label><input class="input-field" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus></div>
                <div><label class="mb-1 block text-sm font-medium">Nombre de usuario</label><input class="input-field" name="username" value="{{ old('username', $user->username ?? '') }}" placeholder="ej. maria.lopez"></div>
                <div><label class="mb-1 block text-sm font-medium">Correo electrónico *</label><input type="email" class="input-field" name="email" value="{{ old('email', $user->email ?? '') }}" required></div>
                <div><label class="mb-1 block text-sm font-medium">Teléfono</label><input class="input-field" name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="+505 8888 8888"></div>
            </div>
        </section>

        @unless($editing)
        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Contraseña inicial</h2>
            <p class="mt-1 text-xs text-slate-500">Política: {{ $passwordPolicy }}.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1 block text-sm font-medium">Contraseña *</label><input type="password" class="input-field" name="password" required autocomplete="new-password"></div>
                <div><label class="mb-1 block text-sm font-medium">Confirmar *</label><input type="password" class="input-field" name="password_confirmation" required autocomplete="new-password"></div>
            </div>
            <label class="mt-4 flex items-start gap-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                <input type="checkbox" name="force_password_change" value="1" class="mt-0.5 rounded" {{ old('force_password_change', true) ? 'checked' : '' }}>
                <span><strong>Exigir cambio en el próximo ingreso.</strong><br>Recomendado para contraseñas temporales.</span>
            </label>
        </section>
        @endunless

        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Roles</h2>
            <p class="mt-1 text-xs text-slate-500">Definen el conjunto principal de accesos.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($roles as $role)
                <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="mt-0.5 rounded" {{ $selectedRoles->contains($role->id) ? 'checked' : '' }}>
                    <span><span class="block text-sm font-semibold">{{ $role->name }}</span><span class="text-xs text-slate-500">{{ $role->description ?: $role->slug }}</span></span>
                </label>
                @endforeach
            </div>
        </section>

        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold text-slate-900">Permisos especiales</h2>
            <p class="mt-1 text-xs text-slate-500">Se agregan a los permisos heredados de sus roles.</p>
            <div class="mt-4 space-y-4">
                @forelse($permissionsByModule as $module => $modulePermissions)
                <div><h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $module) }}</h3>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($modulePermissions as $permission)
                        <label class="flex items-start gap-2 rounded-lg border border-slate-200 p-2.5 text-sm"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="mt-0.5 rounded" {{ $selectedPermissions->contains($permission->id) ? 'checked' : '' }}><span>{{ $permission->name }}</span></label>
                        @endforeach
                    </div>
                </div>
                @empty <p class="text-sm text-slate-500">No hay permisos configurados.</p> @endforelse
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card p-5">
            <h2 class="font-semibold text-slate-900">Foto de perfil</h2>
            @if($editing && $user->profile_photo)
                <img src="{{ Storage::disk('public')->url($user->profile_photo) }}" class="mx-auto mt-4 h-28 w-28 rounded-2xl object-cover" alt="Foto de {{ $user->name }}">
                <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_profile_photo" value="1" class="rounded"> Quitar foto actual</label>
            @endif
            <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700">
            <p class="mt-2 text-xs text-slate-500">JPG, PNG o WebP; máximo 2 MB.</p>
        </section>
        @unless($editing)
        <section class="card p-5"><h2 class="font-semibold">Estado inicial</h2><label class="mt-3 flex items-center gap-3 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', true) ? 'checked' : '' }}> Usuario activo</label></section>
        @endunless
    </aside>
</div>

<div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
    <a href="{{ $editing ? route('settings.users.show', $user) : route('settings.users') }}" class="btn-outline justify-center">Cancelar</a>
    <button class="btn-primary justify-center">{{ $editing ? 'Guardar cambios' : 'Crear usuario' }}</button>
</div>
