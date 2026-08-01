@extends('layouts.app')

@section('title', 'Configuración')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Centro de Configuración</h1>
            <p class="page-subtitle">Administra usuarios, roles, permisos y configuraciones del sistema</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-4 border-l-4 border-indigo-500">
            <p class="text-xs text-slate-500">Usuarios</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['users'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-violet-500">
            <p class="text-xs text-slate-500">Roles</p>
            <p class="text-2xl font-bold text-violet-600">{{ $stats['roles'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-emerald-500">
            <p class="text-xs text-slate-500">Módulos Activos</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['active_modules'] }}/{{ $stats['total_modules'] }}</p>
        </div>
        <div class="card p-4 border-l-4 border-amber-500">
            <p class="text-xs text-slate-500">Actividad Reciente</p>
            <p class="text-2xl font-bold text-amber-600">{{ $recentActivity->count() }}</p>
        </div>
    </div>

    {{-- Configuration Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- Usuarios --}}
        <a href="{{ route('settings.users') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-indigo-200 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Usuarios</h3>
                    <p class="text-sm text-slate-500">Gestión de usuarios, roles y accesos</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Roles --}}
        <a href="{{ route('settings.roles') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-violet-200 transition-colors">
                        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Roles</h3>
                    <p class="text-sm text-slate-500">Crear y gestionar roles del sistema</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-violet-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Permisos --}}
        <a href="{{ route('settings.permissions') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-200 transition-colors">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Permisos</h3>
                    <p class="text-sm text-slate-500">Configurar permisos por módulo</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-emerald-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Configuración General --}}
        <a href="{{ route('settings.general') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Configuración General</h3>
                    <p class="text-sm text-slate-500">Datos de empresa, moneda, IVA</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Módulos --}}
        <a href="{{ route('settings.modules') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-amber-200 transition-colors">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Módulos</h3>
                    <p class="text-sm text-slate-500">Activar/desactivar módulos del sistema</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-amber-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Seguridad --}}
        <a href="{{ route('settings.security') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-red-200 transition-colors">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Seguridad</h3>
                    <p class="text-sm text-slate-500">Sesión, contraseñas, 2FA</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Apariencia --}}
        <a href="{{ route('settings.appearance') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Apariencia</h3>
                    <p class="text-sm text-slate-500">Tema, colores, logo</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-purple-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

        {{-- Numeraciones --}}
        <a href="{{ route('settings.sequences') }}" class="card p-6 hover:shadow-lg transition-all hover:scale-105 group cursor-pointer">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-cyan-200 transition-colors">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Numeraciones</h3>
                    <p class="text-sm text-slate-500">Consecutivos de facturas y documentos</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

    </div>

    {{-- Recent Activity --}}
    @if($recentActivity->count() > 0)
    <div class="card">
        <div class="p-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Actividad Reciente</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($recentActivity as $log)
            <div class="p-4 flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-900">{{ $log->action }}</p>
                    <p class="text-xs text-slate-500">{{ $log->user?->name ?? 'Sistema' }} · {{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection
