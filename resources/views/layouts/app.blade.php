<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Sistema Agroservicio')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="flex h-screen overflow-hidden">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-[#1E5631] text-white flex flex-col relative z-10">

            {{-- Logo --}}
            <div class="p-6 border-b border-green-700">
                <h2 class="text-xl font-bold">AgroCampo</h2>
                <p class="text-xs text-green-200">Sistema Administrativo</p>
            </div>

            {{-- Menu --}}
            <nav class="flex-1 p-4 space-y-2 text-sm">

                <a href="{{ route('dashboard.general') }}"
                    class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-green-700 transition">

                    <span>🏠</span>
                    <span>Dashboard</span>

                </a>


                <a href="{{ route('facturacion.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>🧾</span>
                    <span>Facturación</span>
                </a>

                <a href="{{ route('inventario.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>📦</span>
                    <span>Inventario</span>
                </a>

                <a href="{{ route('proveedores.index') }}"
                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>🏢</span>
                    <span>Proveedores</span>
                </a>


                <a href="{{ route('compras.index') }}"
                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>🧾</span>
                    <span>Compras</span>
                </a>
                <a href="{{ route('clientes.index') }}"
                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>👥</span>
                    <span>Clientes</span>
                </a>

                <a href="{{ route('planilla.index') }}"
                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span> 📋</span>
                    <span>Planilla</span>
                </a>


                @if(auth()->user()?->isAdmin())
                <a href="{{ route('reportes.index') }}" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-green-700 transition">
                    <span>📈</span>
                    <span>Reportes</span>
                </a>
                @endif

            </nav>

            {{-- Usuario --}}
            <div class="p-4 border-t border-green-700 text-xs">
                <p class="text-green-200">Usuario:</p>
                <p class="font-semibold truncate">{{ auth()->user()?->name ?? 'Invitado' }}</p>
                <p class="text-green-300 text-xs mt-1">{{ auth()->user()?->email ?? '' }}</p>
                @if(auth()->user()?->isAdmin())
                    <span class="inline-block mt-2 px-2 py-0.5 bg-red-500 text-white rounded text-xs font-bold">ADMIN</span>
                @else
                    <span class="inline-block mt-2 px-2 py-0.5 bg-blue-500 text-white rounded text-xs">Usuario</span>
                @endif
            </div>

        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="flex-1 flex flex-col">

            {{-- NAVBAR SUPERIOR --}}
            <header class="sticky top-0 bg-white z-30 shadow-sm p-4 flex justify-between items-center">

                <h1 class="text-lg font-semibold text-gray-700">
                    @yield('title')
                </h1>

                <div class="flex items-center space-x-6 text-sm text-gray-600">

                    <span>📅 {{ date('d/m/Y') }}</span>

                    <button class="relative">
                        🔔
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs px-1 rounded-full">
                            3
                        </span>
                    </button>

                    <div class="flex items-center space-x-3">
                        <span class="text-sm text-gray-700">{{ auth()->user()?->name ?? 'Usuario' }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1.5 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Salir
                            </button>
                        </form>
                    </div>

                </div>

            </header>

            {{-- CONTENIDO DINÁMICO --}}
            <main class="flex-1 p-8 overflow-y-auto">
                @yield('content')
            </main>

        </div>

    </div>

    {{-- Scripts from views (placed inside body) --}}
    @stack('scripts')

</body>

</html>