<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Sistema Agroservicio')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#3730a3', 900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }

        .nav-link {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.625rem 1rem; border-radius: 0.75rem;
            transition: all 0.15s; font-size: 0.875rem; font-weight: 500;
        }
        .nav-link-active {
            background: rgba(99, 102, 241, 0.2); color: #fff;
            border-left: 3px solid #818cf8;
        }
        .nav-link-inactive { color: #cbd5e1; }
        .nav-link-inactive:hover { background: #334155; color: #fff; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #4f46e5; color: #fff; font-weight: 600;
            padding: 0.5rem 1rem; border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.05); transition: all 0.15s;
        }
        .btn-primary:hover { background: #4338ca; }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: #475569; color: #fff; font-weight: 600;
            padding: 0.5rem 1rem; border-radius: 0.75rem; transition: all 0.15s;
        }
        .btn-secondary:hover { background: #334155; }

        .btn-outline {
            display: inline-flex; align-items: center; gap: 0.5rem;
            border: 1px solid #cbd5e1; color: #334155; font-weight: 500;
            padding: 0.5rem 1rem; border-radius: 0.75rem; transition: all 0.15s;
        }
        .btn-outline:hover { background: #f8fafc; }

        .card {
            background: #fff; border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.05); border: 1px solid #e2e8f0;
        }

        .input-field, .select-field {
            width: 100%; padding: 0.5rem 1rem; font-size: 0.875rem;
            border: 1px solid #cbd5e1; border-radius: 0.75rem; background: #fff;
        }
        .input-field:focus, .select-field:focus {
            outline: none; border-color: #4f46e5;
            box-shadow: 0 0 0 1px #4f46e5;
        }

        .table-agro thead { background: #1e293b; color: #fff; }
        .table-agro thead th {
            padding: 0.75rem 1.5rem; text-align: left;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
        }
        .table-agro tbody tr {
            border-top: 1px solid #f1f5f9; transition: background 0.15s;
        }
        .table-agro tbody tr:hover { background: #f8fafc; }
        .table-agro tbody td { padding: 0.75rem 1.5rem; font-size: 0.875rem; color: #334155; }

        .badge-success { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #d1fae5; color: #047857; }
        .badge-warning { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #fef3c7; color: #b45309; }
        .badge-danger  { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #fee2e2; color: #b91c1c; }
        .badge-info    { padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: #e0e7ff; color: #4338ca; }

        .page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
        .page-subtitle { font-size: 0.875rem; color: #64748b; margin-top: 0.125rem; }

        .tab-link { padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-bottom: 2px solid transparent; transition: color 0.15s; }
        .tab-link-active { color: #4f46e5; border-bottom-color: #4f46e5; }
        .tab-link-inactive { color: #64748b; }
        .tab-link-inactive:hover { color: #334155; border-bottom-color: #cbd5e1; }
    </style>
</head>

<body class="bg-slate-100">

    <div class="flex h-screen overflow-hidden">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-slate-800 text-white flex flex-col relative z-10 shrink-0">

            <div class="p-6 border-b border-slate-700">
                <h2 class="text-xl font-bold tracking-tight">EsteliPOS</h2>
                <p class="text-xs text-slate-400 mt-1">Sistema Administrativo</p>
            </div>

            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

                @php
                    $navItems = [
                        ['route' => 'facturacion.pos', 'label' => 'Punto de Venta', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'highlight' => true],
                        ['route' => 'dashboard.general', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'facturacion.index', 'label' => 'Facturación', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'match' => 'facturacion.*'],
                        ['route' => 'proformas.index', 'label' => 'Proformas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'match' => 'proformas.*'],
                        ['route' => 'inventario.index', 'label' => 'Inventario', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'match' => 'inventario.*'],
                        ['route' => 'proveedores.index', 'label' => 'Proveedores', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'match' => 'proveedores.*'],
                        ['route' => 'compras.index', 'label' => 'Compras', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'match' => 'compras.*'],
                        ['route' => 'clientes.index', 'label' => 'Clientes', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'match' => 'clientes.*'],
                        ['route' => 'creditos.index', 'label' => 'Créditos', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'match' => 'creditos.*'],
                        ['route' => 'planilla.index', 'label' => 'Planilla', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'match' => 'planilla.*'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $isActive = isset($item['match'])
                            ? request()->routeIs($item['match']) && !request()->routeIs('facturacion.pos')
                            : request()->routeIs($item['route']);
                        if ($item['route'] === 'facturacion.pos') {
                            $isActive = request()->routeIs('facturacion.pos');
                        }
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ $isActive ? 'nav-link-active' : 'nav-link-inactive' }} {{ ($item['highlight'] ?? false) && !$isActive ? 'ring-1 ring-indigo-500/30' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                        </svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

                @if(auth()->user()?->isAdmin())
                <a href="{{ route('reportes.index') }}"
                   class="nav-link {{ request()->routeIs('reportes.*') ? 'nav-link-active' : 'nav-link-inactive' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Reportes</span>
                </a>
                @endif

            </nav>

            <div class="p-4 border-t border-slate-700 text-xs">
                <p class="text-slate-400 mb-1">Usuario</p>
                <p class="font-semibold text-white truncate">{{ auth()->user()?->name ?? 'Invitado' }}</p>
                <p class="text-slate-500 text-xs mt-1 truncate">{{ auth()->user()?->email ?? '' }}</p>
                @if(auth()->user()?->isAdmin())
                    <span class="inline-block mt-2 px-2 py-0.5 bg-indigo-600 text-white rounded text-xs font-semibold">ADMIN</span>
                @else
                    <span class="inline-block mt-2 px-2 py-0.5 bg-slate-600 text-white rounded text-xs">Usuario</span>
                @endif
            </div>

        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="flex-1 flex flex-col min-w-0">

            @unless(View::hasSection('hide-header'))
            <header class="sticky top-0 bg-white border-b border-slate-200 z-30 px-6 py-3 flex justify-between items-center shrink-0">

                <h1 class="text-lg font-semibold text-slate-800">@yield('title')</h1>

                <div class="flex items-center space-x-3">
                    <div class="hidden md:flex items-center space-x-2 border-r border-slate-200 pr-4">
                        <a href="{{ route('facturacion.pos') }}" class="btn-primary text-sm py-1.5 px-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Venta
                        </a>
                    </div>

                    <div class="hidden lg:flex items-center space-x-2">
                        <a href="/settings" class="btn-outline text-sm py-1 px-3" title="Configuraciones">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path></svg>
                            Configuración
                        </a>

                        <a href="/users" class="btn-outline text-sm py-1 px-3" title="Usuarios">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11c1.657 0 3-1.567 3-3.5S17.657 4 16 4s-3 1.567-3 3.5S14.343 11 16 11zM6 21v-2a4 4 0 014-4h4"></path></svg>
                            Usuarios
                        </a>

                        <a href="/calendar" class="btn-outline text-sm py-1 px-3" title="Calendario">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ date('d/m/Y') }}
                        </a>

                        <a href="/notifications" class="btn-outline text-sm py-1 px-3" title="Notificaciones">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </a>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center gap-1 text-slate-500 hover:text-red-600 hover:bg-red-50 px-3 py-2 rounded-xl transition-colors text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Salir
                        </button>
                    </form>
                </div>

            </header>
            @endunless

            @if(session('success') || session('error') || $errors->any())
            <div class="px-6 pt-4 shrink-0">
                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
            </div>
            @endif

            <main class="flex-1 overflow-y-auto bg-slate-50 @yield('main-class', 'p-6')">
                @yield('content')
            </main>

        </div>

    </div>

    @stack('scripts')

</body>

</html>
