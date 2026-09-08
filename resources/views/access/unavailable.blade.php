@extends('layouts.app')

@section('title', 'Acceso limitado')

@section('content')
<div class="mx-auto flex min-h-[60vh] max-w-2xl items-center justify-center">
    <section class="card w-full p-8 text-center sm:p-10">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold text-slate-900">Tu cuenta tiene acceso limitado</h1>
        <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-600">
            Por el momento no tienes módulos habilitados. El sistema continúa funcionando normalmente;
            solicita a un administrador que asigne los permisos necesarios a tu usuario.
        </p>
        <form class="mt-7" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-outline">Cerrar sesión</button>
        </form>
    </section>
</div>
@endsection
