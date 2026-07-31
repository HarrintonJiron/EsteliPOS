@extends('layouts.app')

@section('title', 'Permisos')

@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Permisos</h1>
            <p class="page-subtitle">Configurar permisos por módulo</p>
        </div>
    </div>

    <div class="card">
        <div class="p-4 text-center text-slate-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <p>Sección de permisos en desarrollo</p>
            <p class="text-sm mt-2">Próximamente: Gestión de permisos por módulo y acción</p>
        </div>
    </div>
</div>

@endsection
