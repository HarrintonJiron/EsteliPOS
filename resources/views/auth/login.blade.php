<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Agroservicio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-pattern {
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.1) 1px, transparent 0);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-800 via-green-700 to-green-900 min-h-screen flex items-center justify-center p-4 bg-pattern">
    
    <div class="w-full max-w-md">
        {{-- Logo y título --}}
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl mx-auto flex items-center justify-center shadow-lg mb-4">
                <div class="text-center">
                    <div class="text-2xl font-black text-green-800">AS</div>
                    <div class="text-xs font-bold text-green-600">AGRO</div>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white">Agroservicio S.A.</h1>
            <p class="text-green-200 text-sm">Sistema de Gestión Integral</p>
        </div>

        {{-- Card de login --}}
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-700 to-green-600 px-6 py-4">
                <h2 class="text-white font-semibold text-lg">Iniciar Sesión</h2>
            </div>
            
            <div class="p-6 space-y-5">
                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <p class="text-red-700 text-sm font-medium">Error de autenticación</p>
                        <p class="text-red-600 text-sm">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                               placeholder="admin@agroservicio.com" required autofocus>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                               placeholder="••••••••" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                        </label>
                        <a href="#" class="text-sm text-green-600 hover:text-green-700">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-green-700 to-green-600 text-white font-semibold py-3 rounded-lg hover:from-green-800 hover:to-green-700 transition shadow-lg">
                        Ingresar al Sistema
                    </button>
                </form>

                {{-- Info de acceso demo --}}
                <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-500">
                    <p class="font-medium text-gray-700 mb-1">Credenciales de prueba:</p>
                    <p><span class="font-medium">Admin:</span> admin@agroservicio.com / password</p>
                    <p><span class="font-medium">Usuario:</span> usuario@agroservicio.com / password</p>
                </div>
            </div>
        </div>

        <p class="text-center text-green-200 text-sm mt-6">
            © {{ date('Y') }} Agroservicio S.A. - Todos los derechos reservados
        </p>
    </div>

</body>
</html>
