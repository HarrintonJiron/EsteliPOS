<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - {{ $companyProfile['system_name'] ?? 'Agroservicio POS' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-pattern {
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 via-slate-700 to-indigo-900 min-h-screen flex items-center justify-center p-4 bg-pattern">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="h-28 w-28 bg-white rounded-3xl mx-auto flex items-center justify-center shadow-xl mb-5">
                <div class="text-center">
                    @if($companyProfile['company_logo_url'] ?? null)
                        <img src="{{ $companyProfile['company_logo_url'] }}" class="h-24 w-24 object-contain" alt="Logo de {{ $companyProfile['system_name'] ?? 'la empresa' }}" data-company-logo>
                    @else
                        <div class="text-2xl font-black text-indigo-700">{{ mb_strtoupper(mb_substr($companyProfile['system_name'] ?? 'AS', 0, 2)) }}</div>
                    @endif
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ $companyProfile['system_name'] ?? 'Agroservicio POS' }}</h1>
            <p class="text-slate-300 text-sm">{{ $companyProfile['company_name'] ?? 'Sistema de Gestión Integral' }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h2 class="text-white font-semibold text-lg">Iniciar Sesión</h2>
            </div>

            <div class="p-6 space-y-5">
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 p-4 rounded-xl">
                        <p class="text-red-700 text-sm font-medium">Error de autenticación</p>
                        <p class="text-red-600 text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Correo o nombre de usuario</label>
                        <input type="text" name="login" value="{{ old('login') }}"
                               class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="correo@empresa.com o usuario" required autofocus autocomplete="username">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <input type="password" name="password"
                               class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-slate-600">Recordarme</span>
                        </label>
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition shadow-lg">
                        Ingresar al Sistema
                    </button>
                </form>

            </div>
        </div>

        <div class="mt-6 text-center" data-developer-credit>
            <p class="text-sm text-slate-400">© {{ date('Y') }} {{ $companyProfile['company_name'] ?? $companyProfile['system_name'] ?? 'Agroservicio' }}</p>
            <p class="mt-2 text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Desarrollado por</p>
            <p class="mt-1 text-sm font-semibold tracking-wide text-slate-300">Northlink Microsystem</p>
        </div>
    </div>

</body>
</html>
