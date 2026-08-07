@php
    $appearanceSettings = \App\Models\Setting::getByGroup('appearance');
    $theme = $appearanceSettings['theme'] ?? 'light';
    $primaryColor = $appearanceSettings['primary_color'] ?? '#0d9488';
    $systemName = $companyProfile['system_name'] ?? $appearanceSettings['system_name'] ?? 'EsteliPOS';
    $companyLogoUrl = $companyProfile['company_logo_url'] ?? null;

    $isDark = false;
    if ($theme === 'dark') {
        $isDark = true;
    } elseif ($theme === 'auto') {
        $isDark = isset($_COOKIE['theme']) ? $_COOKIE['theme'] === 'dark' : false;
    }

    $bodyBg = $isDark ? 'bg-slate-900' : 'bg-slate-100';
    $sidebarBg = $isDark ? 'bg-slate-950' : 'bg-slate-900';
    $sidebarBorder = $isDark ? 'border-slate-800' : 'border-white/10';
@endphp

<!DOCTYPE html>
<html lang="es" class="{{ $isDark ? 'dark' : '' }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $systemName)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --ui-primary: {{ $primaryColor }};
            --ui-primary-hover: {{ $primaryColor }};
            --ui-primary-dark: #0f766e;
        }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

        /* ── Sidebar (layout-specific) ── */
        #app-sidebar {
            position: relative;
            width: 17rem;
            overflow: visible;
            transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1), transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #app-sidebar.is-collapsed {
            width: 5.5rem;
        }

        .sidebar-collapse-pill {
            display: none;
            position: absolute;
            top: 50%;
            right: 0;
            z-index: 70;
            width: 1.75rem;
            height: 2.75rem;
            transform: translate(50%, -50%);
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
            color: #4f46e5;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s, transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-collapse-pill:hover {
            background: #4f46e5;
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.35);
        }
        .sidebar-collapse-pill:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35), 0 8px 20px rgba(15, 23, 42, 0.18);
        }
        .sidebar-collapse-pill svg {
            width: 1rem;
            height: 1rem;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #app-sidebar.is-collapsed .sidebar-collapse-pill svg {
            transform: rotate(180deg);
        }
        @media (min-width: 1024px) {
            .sidebar-collapse-pill { display: inline-flex; }
        }

        .nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0.75rem;
            transition: background 0.15s, color 0.15s, box-shadow 0.15s;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .nav-link-active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.28), rgba(139, 92, 246, 0.18));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(129, 140, 248, 0.35);
        }
        .nav-link-active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            border-radius: 0 4px 4px 0;
            background: #818cf8;
        }
        .nav-link-inactive { color: #94a3b8; }
        .nav-link-inactive:hover {
            background: rgba(148, 163, 184, 0.1);
            color: #f8fafc;
        }
        .nav-link-highlight:not(.nav-link-active) {
            background: rgba(99, 102, 241, 0.12);
            color: #c7d2fe;
            box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.25);
        }
        .nav-link-highlight:not(.nav-link-active):hover {
            background: rgba(99, 102, 241, 0.22);
            color: #fff;
        }

        #app-sidebar.is-collapsed .nav-link {
            justify-content: center;
            padding-left: 0.7rem;
            padding-right: 0.7rem;
            gap: 0;
        }
        #app-sidebar.is-collapsed .nav-link-active::before { display: none; }
        #app-sidebar.is-collapsed .nav-label,
        #app-sidebar.is-collapsed .sidebar-brand-text,
        #app-sidebar.is-collapsed .sidebar-user-meta {
            opacity: 0;
            width: 0;
            height: 0;
            overflow: hidden;
            pointer-events: none;
            margin: 0;
            padding: 0;
        }
        #app-sidebar.is-collapsed .sidebar-section-label {
            display: none;
        }
        #app-sidebar.is-collapsed .sidebar-brand {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        #app-sidebar .sidebar-user-avatar {
            width: 2.75rem;
            height: 2.75rem;
            font-size: 0.95rem;
        }
        #app-sidebar.is-collapsed .sidebar-user-avatar {
            width: 2.5rem;
            height: 2.5rem;
        }
        #app-sidebar.is-collapsed .sidebar-footer {
            align-items: center;
        }
        #app-sidebar.is-collapsed .sidebar-user-card {
            justify-content: center;
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        /* Tooltip when collapsed */
        #app-sidebar.is-collapsed .nav-link[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 0.75rem);
            top: 50%;
            transform: translateY(-50%);
            background: #0f172a;
            color: #f8fafc;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.4rem 0.65rem;
            border-radius: 0.5rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(148, 163, 184, 0.2);
            z-index: 60;
            transition: opacity 0.15s;
        }
        #app-sidebar.is-collapsed .nav-link[data-tooltip]:hover::after {
            opacity: 1;
        }

        .sidebar-section-label {
            padding: 0.75rem 0.85rem 0.35rem;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            transition: opacity 0.2s;
        }

        @media print {
            aside, header, form, .no-print { display: none !important; }
            body, main { background: #fff !important; overflow: visible !important; }
            .flex.h-screen { display: block !important; height: auto !important; overflow: visible !important; }
            main { padding: 0 !important; }
            .card { box-shadow: none !important; break-inside: avoid; }
        }
    </style>
</head>

<body class="{{ $bodyBg }}">

    <div class="flex h-screen overflow-hidden">

        <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/55 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

        {{-- SIDEBAR --}}
        <aside id="app-sidebar"
               class="fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col {{ $sidebarBg }} text-white shadow-2xl lg:static lg:z-40 lg:translate-x-0 lg:shadow-none"
               aria-label="Navegación principal">

            {{-- Brand --}}
            <div class="sidebar-brand relative flex items-start justify-between gap-3 border-b {{ $sidebarBorder }} px-4 py-5 lg:px-6">
                <div class="sidebar-brand-text min-w-0 flex-1">
                    @if($companyLogoUrl)
                        <img src="{{ $companyLogoUrl }}"
                             alt="Logo de {{ $systemName }}"
                             class="mb-3 h-20 w-full max-w-48 object-contain object-left"
                             data-company-logo>
                    @endif
                    <h2 class="truncate text-xl font-bold tracking-tight text-white">{{ $systemName }}</h2>
                    <p class="mt-1 text-xs text-slate-400">Sistema Administrativo</p>
                </div>

                <button id="sidebar-close" type="button"
                        class="absolute right-3 top-3 rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400 lg:hidden"
                        aria-label="Cerrar menú">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 space-y-0.5 overflow-y-auto overflow-x-hidden px-3 py-3">
                @php
                    $navSections = [
                        [
                            'label' => 'Principal',
                            'items' => [
                                ['route' => 'facturacion.pos', 'label' => 'Punto de Venta', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'highlight' => true],
                                ['route' => 'dashboard.general', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ],
                        ],
                        [
                            'label' => 'Operaciones',
                            'items' => [
                                ['route' => 'facturacion.index', 'label' => 'Facturación', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'match' => 'facturacion.*'],
                                ['route' => 'proformas.index', 'label' => 'Proformas', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'match' => 'proformas.*'],
                                ['route' => 'reparaciones.index', 'label' => 'Reparaciones', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'match' => 'reparaciones.*'],
                                ['route' => 'compras.index', 'label' => 'Compras', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'match' => 'compras.*'],
                                ['route' => 'inventario.index', 'label' => 'Inventario', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'match' => 'inventario.*'],
                            ],
                        ],
                        [
                            'label' => 'Gestión',
                            'items' => [
                                ['route' => 'clientes.index', 'label' => 'Clientes', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'match' => 'clientes.*'],
                                ['route' => 'proveedores.index', 'label' => 'Proveedores', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'match' => 'proveedores.*'],
                                ['route' => 'creditos.index', 'label' => 'Créditos', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'match' => 'creditos.*'],
                                ['route' => 'planilla.index', 'label' => 'Planilla', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'match' => ['planilla.*', 'nomina.*', 'leave.*', 'loans.*', 'bonuses.*', 'deductions.*', 'employees.*']],
                            ],
                        ],
                    ];

                    $moduleByRoute = [
                        'facturacion.pos' => 'ventas', 'facturacion.index' => 'ventas',
                        'proformas.index' => 'proformas', 'reparaciones.index' => 'reparaciones',
                        'inventario.index' => 'inventario', 'proveedores.index' => 'proveedores',
                        'compras.index' => 'compras', 'clientes.index' => 'clientes',
                        'creditos.index' => 'creditos', 'planilla.index' => 'planilla',
                    ];
                @endphp

                @foreach($navSections as $section)
                    @php
                        $visibleItems = collect($section['items'])->filter(function ($item) use ($moduleByRoute, $accessibleModuleSlugs) {
                            if (! isset($moduleByRoute[$item['route']])) {
                                return true;
                            }

                            return $accessibleModuleSlugs->contains($moduleByRoute[$item['route']]);
                        });
                    @endphp

                    @continue($visibleItems->isEmpty())

                    <p class="sidebar-section-label">{{ $section['label'] }}</p>

                    @foreach($visibleItems as $item)
                        @php
                            $match = $item['match'] ?? $item['route'];
                            $isActive = is_array($match)
                                ? request()->routeIs(...$match)
                                : request()->routeIs($match);

                            if ($item['route'] === 'facturacion.pos') {
                                $isActive = request()->routeIs('facturacion.pos');
                            } elseif (($item['match'] ?? null) === 'facturacion.*') {
                                $isActive = request()->routeIs('facturacion.*') && ! request()->routeIs('facturacion.pos');
                            }

                            $linkClass = $isActive ? 'nav-link-active' : 'nav-link-inactive';
                            if (($item['highlight'] ?? false) && ! $isActive) {
                                $linkClass .= ' nav-link-highlight';
                            }
                        @endphp
                        <a href="{{ route($item['route']) }}"
                           class="nav-link {{ $linkClass }}"
                           data-tooltip="{{ $item['label'] }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"></path>
                            </svg>
                            <span class="nav-label truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach

                @php
                    $systemLinks = [];
                    if ($accessibleModuleSlugs->contains('contabilidad') && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('contabilidad.view'))) {
                        $systemLinks[] = [
                            'route' => 'contabilidad.dashboard',
                            'label' => 'Contabilidad',
                            'match' => 'contabilidad.*',
                            'icon' => 'M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z',
                        ];
                    }
                    if ($accessibleModuleSlugs->contains('reportes') && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('reportes.view'))) {
                        $systemLinks[] = [
                            'route' => 'reportes.index',
                            'label' => 'Reportes',
                            'match' => 'reportes.*',
                            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        ];
                    }
                    if ($accessibleModuleSlugs->contains('configuracion') && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.view'))) {
                        $systemLinks[] = [
                            'route' => 'settings.index',
                            'label' => 'Configuración',
                            'match' => 'settings.*',
                            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                            'icon_extra' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                        ];
                    }
                @endphp

                @if(count($systemLinks))
                    <p class="sidebar-section-label">Sistema</p>
                    @foreach($systemLinks as $item)
                        @php $isActive = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="nav-link {{ $isActive ? 'nav-link-active' : 'nav-link-inactive' }}"
                           data-tooltip="{{ $item['label'] }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"></path>
                                @if(!empty($item['icon_extra']))
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon_extra'] }}"></path>
                                @endif
                            </svg>
                            <span class="nav-label truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endif
            </nav>

            {{-- User footer --}}
            <div class="sidebar-footer flex flex-col gap-3 border-t {{ $sidebarBorder }} p-3">
                <div class="sidebar-user-card flex items-center gap-3 rounded-xl bg-white/5 px-2.5 py-2.5">
                    <div class="sidebar-user-avatar flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 font-bold uppercase shadow-lg shadow-indigo-500/20 transition-all duration-280">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="sidebar-user-meta min-w-0 overflow-hidden transition-all duration-200">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Invitado' }}</p>
                        <p class="truncate text-[11px] text-slate-400">{{ auth()->user()?->email ?? '' }}</p>
                        @if(auth()->user()?->isAdmin())
                            <span class="mt-1 inline-block rounded-md bg-indigo-500/20 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-indigo-300">ADMIN</span>
                        @else
                            <span class="mt-1 inline-block rounded-md bg-slate-500/20 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-slate-300">Usuario</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Viñeta contraer / expandir --}}
            <button id="sidebar-toggle" type="button"
                    class="sidebar-collapse-pill"
                    aria-label="Contraer o expandir menú"
                    aria-controls="app-sidebar"
                    aria-expanded="true"
                    title="Contraer menú">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="flex min-w-0 flex-1 flex-col">

            @unless(View::hasSection('hide-header'))
            <header class="app-header sticky top-0 z-30 flex shrink-0 items-center justify-between gap-3 px-4 py-3 sm:px-6">

                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button id="sidebar-open" type="button"
                            class="btn-ghost btn-icon lg:hidden"
                            aria-label="Abrir menú" aria-controls="app-sidebar" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        @hasSection('breadcrumb')
                            <nav class="breadcrumb hidden sm:flex" aria-label="Breadcrumb">@yield('breadcrumb')</nav>
                        @endif
                        <h1 class="truncate text-lg font-semibold text-slate-800">@yield('title')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    @if($accessibleModuleSlugs->contains('ventas'))
                    <div class="hidden items-center border-r border-slate-200 pr-3 md:flex">
                        <a href="{{ route('facturacion.pos') }}" class="btn-primary btn-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Nueva Venta
                        </a>
                    </div>
                    @endif

                    <div class="hidden items-center gap-2 lg:flex">
                        @if($accessibleModuleSlugs->contains('configuracion') && (auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.view')))
                        <a href="{{ route('settings.index') }}" class="btn-outline btn-sm" title="Configuraciones">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>

                        @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('configuracion.manage_users'))
                        <a href="{{ route('settings.users') }}" class="btn-outline btn-sm" title="Usuarios">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11c1.657 0 3-1.567 3-3.5S17.657 4 16 4s-3 1.567-3 3.5S14.343 11 16 11zM6 21v-2a4 4 0 014-4h4"/></svg>
                        </a>
                        @endif
                        @endif

                        <span class="btn-outline btn-sm cursor-default text-slate-500" title="Fecha actual">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ date('d/m/Y') }}
                        </span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn-ghost btn-sm text-slate-500 hover:!text-red-600 hover:!bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span class="hidden sm:inline">Salir</span>
                        </button>
                    </form>
                </div>

            </header>
            @endunless

            @if(session('success'))
                <div class="hidden" data-ui-toast="success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="hidden" data-ui-toast="error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
            <div class="shrink-0 px-4 pt-4 sm:px-6">
                <div class="ui-alert ui-alert-error">
                    <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            </div>
            @endif

            <main class="app-main flex-1 overflow-y-auto @yield('main-class', 'p-4 sm:p-6 lg:p-8')">
                <div class="mx-auto max-w-[1600px]">
                    @hasSection('back')
                        <div class="mb-4">
                            @yield('back')
                        </div>
                    @elseif(!View::hasSection('hide_back') && !empty($backNavigation))
                        <div class="mb-4">
                            <x-ui.back-button :href="$backNavigation['href']" :label="$backNavigation['label'] ?? 'Regresar'" />
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>

        </div>

    </div>

    <div id="ui-toast-container" class="ui-toast-container" aria-live="polite"></div>

    <script src="{{ asset('js/app-ui.js') }}"></script>
    <script>
        (() => {
            const sidebar = document.getElementById('app-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const openButton = document.getElementById('sidebar-open');
            const closeButton = document.getElementById('sidebar-close');
            const toggleButton = document.getElementById('sidebar-toggle');
            const STORAGE_KEY = 'agroservicio.sidebar.collapsed';

            const isDesktop = () => window.innerWidth >= 1024;

            const setMobileOpen = (open) => {
                sidebar.classList.toggle('-translate-x-full', !open);
                overlay.classList.toggle('hidden', !open);
                openButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('overflow-hidden', open && !isDesktop());
                if (open) closeButton?.focus();
            };

            const setCollapsed = (collapsed) => {
                sidebar.classList.toggle('is-collapsed', collapsed);
                toggleButton?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggleButton?.setAttribute('title', collapsed ? 'Expandir menú' : 'Contraer menú');
                toggleButton?.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
                localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
            };

            // Restore desktop collapse preference
            const savedCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
            if (isDesktop() && savedCollapsed) {
                setCollapsed(true);
            }

            openButton?.addEventListener('click', () => setMobileOpen(true));
            closeButton?.addEventListener('click', () => setMobileOpen(false));
            overlay?.addEventListener('click', () => setMobileOpen(false));

            toggleButton?.addEventListener('click', () => {
                setCollapsed(!sidebar.classList.contains('is-collapsed'));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && openButton?.getAttribute('aria-expanded') === 'true') {
                    setMobileOpen(false);
                }
            });

            window.addEventListener('resize', () => {
                if (isDesktop()) {
                    setMobileOpen(false);
                    document.body.classList.remove('overflow-hidden');
                    if (localStorage.getItem(STORAGE_KEY) === '1') {
                        sidebar.classList.add('is-collapsed');
                    }
                } else {
                    sidebar.classList.remove('is-collapsed');
                }
            });
        })();
    </script>

    @stack('scripts')

</body>

</html>
