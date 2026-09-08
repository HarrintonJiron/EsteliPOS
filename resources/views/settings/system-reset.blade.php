@extends('layouts.app')

@section('title', 'Ambiente de pruebas')

@section('content')
<div class="mx-auto max-w-3xl space-y-6" id="system-reset-page">
    <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('settings.index') }}" class="hover:text-indigo-600">Configuración</a>
        <span aria-hidden="true">/</span>
        <span class="font-medium text-red-700" aria-current="page">Ambiente de pruebas</span>
    </nav>

    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-600">Zona de riesgo</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Reiniciar el sistema</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Esta herramienta prepara el sistema para repetir pruebas desde cero. Antes de borrar los datos se crea un respaldo local recuperable.</p>
    </header>

    <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-900" role="alert">
        <p class="font-bold">Se eliminarán todas las operaciones y datos comerciales actuales.</p>
        <p class="mt-2 leading-6">Ventas, compras, clientes, proveedores, productos, inventario, caja, crédito, contabilidad y auditoría serán reconstruidos. Se conservarán la cuenta, contraseña y acceso del administrador que ejecuta el proceso.</p>
    </div>

    <form method="POST" action="{{ route('settings.system-reset.store') }}" class="card space-y-6 p-5 sm:p-7" id="system-reset-form" novalidate>
        @csrf

        <fieldset class="space-y-3">
            <legend class="font-semibold text-slate-900">1. Selecciona el estado inicial</legend>

            <label class="flex cursor-pointer gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="mode" value="clean" class="mt-1" @checked(old('mode', 'clean') === 'clean') required>
                <span>
                    <strong class="block text-slate-900">Sistema limpio</strong>
                    <span class="mt-1 block text-sm leading-5 text-slate-600">Conserva catálogos base y administrador, sin productos, clientes, compras ni ventas de prueba.</span>
                </span>
            </label>

            <label class="flex cursor-pointer gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="mode" value="demo" class="mt-1" @checked(old('mode') === 'demo') required>
                <span>
                    <strong class="block text-slate-900">Ambiente de demostración</strong>
                    <span class="mt-1 block text-sm leading-5 text-slate-600">Carga productos, clientes, proveedores, compras y ventas preparadas para realizar pruebas.</span>
                </span>
            </label>
            @error('mode')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </fieldset>

        <div>
            <label for="reset-password" class="mb-1 block text-sm font-semibold text-slate-900">2. Confirma tu contraseña administrativa</label>
            <input id="reset-password" class="input-field" type="password" name="password" required autocomplete="current-password">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="reset-confirmation" class="mb-1 block text-sm font-semibold text-slate-900">3. Escribe REINICIAR SISTEMA</label>
            <input id="reset-confirmation" class="input-field font-mono uppercase" type="text" name="confirmation" value="{{ old('confirmation') }}" required autocomplete="off" spellcheck="false">
            @error('confirmation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-950">
            <input id="reset-acknowledge" class="mt-0.5 rounded" type="checkbox" name="acknowledge" value="1" @checked(old('acknowledge')) required>
            <span>Comprendo que los datos actuales serán eliminados y que deberé iniciar sesión nuevamente al terminar.</span>
        </label>
        @error('acknowledge')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('settings.index') }}" class="btn-outline justify-center">Cancelar</a>
            <button id="system-reset-submit" type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40" disabled>
                Crear respaldo y reiniciar
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('system-reset-form');
        const password = document.getElementById('reset-password');
        const confirmation = document.getElementById('reset-confirmation');
        const acknowledge = document.getElementById('reset-acknowledge');
        const submit = document.getElementById('system-reset-submit');

        const refresh = () => {
            submit.disabled = !password.value || confirmation.value !== 'REINICIAR SISTEMA' || !acknowledge.checked;
        };

        form.addEventListener('input', refresh);
        form.addEventListener('submit', () => {
            submit.disabled = true;
            submit.textContent = 'Creando respaldo y reiniciando…';
        });
        refresh();
    })();
</script>
@endpush
