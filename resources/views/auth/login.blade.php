@php
    $systemName = $companyProfile['system_name'] ?? 'EsteliPOS';
    $developerName = 'Northlink Microsystem';
    $northlinkLogoUrl = asset('images/northlink-logo.png');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — {{ $systemName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app-ui.css') }}">
    <style>
        :root {
            --login-primary: #0d9488;
            --login-primary-dark: #0f766e;
            --login-primary-light: #14b8a6;
            --login-primary-soft: #f0fdfa;
            --login-primary-glow: rgba(13, 148, 136, 0.18);
            --ui-primary: var(--login-primary);
            --ui-primary-hover: var(--login-primary-dark);
            --ui-primary-soft: var(--login-primary-soft);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            color: var(--ui-text);
        }

        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1024px) {
            .login-shell {
                grid-template-columns: 1.05fr 0.95fr;
            }
        }

        /* ── Hero panel ── */
        .login-hero {
            position: relative;
            min-height: 16rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        @media (min-width: 1024px) {
            .login-hero {
                min-height: 100vh;
                position: sticky;
                top: 0;
                height: 100vh;
            }
        }

        .login-hero-bg {
            position: absolute;
            inset: 0;
            background:
                url('{{ asset('images/login-hero.jpg') }}') center / cover no-repeat;
            transform: scale(1.02);
            animation: login-kenburns 22s ease-in-out infinite alternate;
        }

        @keyframes login-kenburns {
            from { transform: scale(1.02); }
            to   { transform: scale(1.08); }
        }

        .login-hero-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg,
                    rgba(15, 23, 42, 0.35) 0%,
                    rgba(15, 23, 42, 0.55) 40%,
                    rgba(15, 118, 110, 0.88) 100%);
        }

        .login-hero-overlay::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(20, 184, 166, 0.35), transparent 55%),
                radial-gradient(ellipse 50% 40% at 90% 80%, rgba(255, 255, 255, 0.08), transparent 50%);
        }

        .login-hero-content {
            position: relative;
            z-index: 1;
            padding: 2rem 1.75rem;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 16rem;
        }

        @media (min-width: 1024px) {
            .login-hero-content {
                padding: 3rem 3.5rem;
                max-width: none;
                min-height: 100vh;
                justify-content: space-between;
            }
        }

        .login-hero-brand {
            display: none;
        }

        @media (min-width: 1024px) {
            .login-hero-brand {
                display: block;
            }
        }

        .login-hero-northlink {
            display: flex;
            align-items: center;
        }

        .login-hero-northlink img {
            max-height: min(37.5rem, 42vh);
            max-width: min(100%, 90vw);
            width: auto;
            height: auto;
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.95;
        }

        .login-hero-product {
            margin: 1.25rem 0 0;
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            text-transform: none;
            color: rgba(255, 255, 255, 0.9);
        }

        .login-hero-body {
            max-width: 34rem;
        }

        .login-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin-bottom: 1.25rem;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.95);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            backdrop-filter: blur(8px);
        }

        .login-hero-badge span {
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 50%;
            background: #5eead4;
            box-shadow: 0 0 8px #5eead4;
        }

        .login-hero-title {
            margin: 0;
            font-size: clamp(1.75rem, 4vw, 2.75rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.15;
            text-shadow: 0 2px 24px rgba(0, 0, 0, 0.25);
        }

        .login-hero-desc {
            margin: 1rem 0 0;
            font-size: 1rem;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.82);
            max-width: 28rem;
        }

        .login-hero-stats {
            display: none;
            gap: 1.5rem;
            margin-top: 2.5rem;
            padding-top: 1.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        @media (min-width: 1024px) {
            .login-hero-stats {
                display: flex;
            }
        }

        .login-hero-stat strong {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .login-hero-stat span {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.65);
        }

        /* ── Form panel ── */
        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            background:
                radial-gradient(ellipse 70% 50% at 100% 0%, rgba(20, 184, 166, 0.06), transparent 50%),
                linear-gradient(180deg, #ffffff 0%, #f8fffe 100%);
        }

        @media (min-width: 1024px) {
            .login-panel {
                padding: 3rem 2.5rem;
            }
        }

        .login-panel-inner {
            width: 100%;
            max-width: 26rem;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand-northlink {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0 1.25rem;
        }

        @media (min-width: 1024px) {
            .login-brand-northlink {
                display: none;
            }
        }

        .login-brand-northlink img {
            max-height: 4.25rem;
            max-width: min(100%, 16rem);
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .login-brand-system {
            margin: 0;
            font-size: clamp(1.875rem, 5vw, 2.375rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.1;
            color: var(--login-primary-dark);
        }

        .login-brand-tagline {
            margin: 0.5rem 0 0;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.45;
            color: var(--ui-text-muted);
        }

        .login-brand-byline {
            margin: 0.625rem 0 0;
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: #64748b;
        }

        .login-welcome {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--ui-border, #e2e8f0);
        }

        .login-welcome h1 {
            margin: 0;
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.25;
            color: var(--ui-text);
        }

        .login-welcome h1 em {
            font-style: normal;
            color: var(--login-primary);
        }

        .login-welcome p {
            margin: 0.375rem 0 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: var(--ui-text-muted);
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.125rem;
        }

        .login-field .form-label {
            margin-bottom: 0.4375rem;
            color: var(--ui-text);
            font-weight: 500;
        }

        .login-field .input-field {
            padding: 0.6875rem 0.875rem;
            background: #fff;
        }

        .login-field .input-field:focus {
            border-color: var(--login-primary-light);
            box-shadow: 0 0 0 3px var(--login-primary-glow);
        }

        .login-password-wrap { position: relative; }

        .login-password-wrap .input-field {
            padding-right: 5.5rem;
        }

        .login-toggle-pw {
            position: absolute;
            right: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--login-primary);
            cursor: pointer;
            padding: 0.25rem 0.375rem;
            border-radius: 0.375rem;
            transition: background var(--ui-transition);
        }

        .login-toggle-pw:hover {
            background: var(--login-primary-soft);
        }

        .login-remember {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
        }

        .login-remember input {
            width: 0.9375rem;
            height: 0.9375rem;
            accent-color: var(--login-primary);
            cursor: pointer;
        }

        .login-remember span {
            font-size: 0.8125rem;
            color: var(--ui-text-muted);
        }

        .login-submit {
            width: 100%;
            padding: 0.75rem 1rem;
            margin-top: 0.25rem;
            background: linear-gradient(135deg, var(--login-primary-dark), var(--login-primary));
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
        }

        .login-submit:hover {
            background: linear-gradient(135deg, #115e59, var(--login-primary-dark));
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.4);
        }

        .login-secure {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: var(--login-primary-dark);
            opacity: 0.75;
        }

        .login-secure svg {
            width: 0.875rem;
            height: 0.875rem;
            flex-shrink: 0;
        }

        .login-footer {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--ui-border, #e2e8f0);
            text-align: center;
        }

        .login-footer p {
            margin: 0;
            font-size: 0.6875rem;
            color: #94a3b8;
        }

        .login-footer .login-credit {
            margin-top: 0.375rem;
            font-size: 0.625rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="login-shell">
        {{-- Panel visual --}}
        <aside class="login-hero" aria-hidden="true">
            <div class="login-hero-bg"></div>
            <div class="login-hero-overlay"></div>
            <div class="login-hero-content">
                <div class="login-hero-brand">
                    <div class="login-hero-northlink">
                        <img src="{{ $northlinkLogoUrl }}" alt="{{ $developerName }}">
                    </div>
                    <p class="login-hero-product">{{ $systemName }}</p>
                </div>

                <div class="login-hero-body">
                <div class="login-hero-badge">
                    <span></span>
                    Hecho para PYMEs en Nicaragua
                </div>
                <h2 class="login-hero-title">Tu negocio local, bajo control</h2>
                <p class="login-hero-desc">
                    Ventas en córdobas, inventario, facturación y reportes — pensado para ferreterías, misceláneas, distribuidoras y comercios locales en Nicaragua.
                </p>
                <div class="login-hero-stats">
                    <div class="login-hero-stat">
                        <strong>C$</strong>
                        <span>Ventas locales</span>
                    </div>
                    <div class="login-hero-stat">
                        <strong>PYME</strong>
                        <span>Fácil de usar</span>
                    </div>
                    <div class="login-hero-stat">
                        <strong>NI</strong>
                        <span>Desde Estelí</span>
                    </div>
                </div>
                </div>
            </div>
        </aside>

        {{-- Panel formulario --}}
        <main class="login-panel">
            <div class="login-panel-inner">
                <div class="login-brand">
                    <div class="login-brand-northlink">
                        <img src="{{ $northlinkLogoUrl }}" alt="{{ $developerName }}">
                    </div>
                    <h1 class="login-brand-system">{{ $systemName }}</h1>
                    <p class="login-brand-tagline">Punto de venta para PYMEs nicaragüenses</p>
                    <p class="login-brand-byline">{{ $developerName }}</p>
                </div>

                <div class="login-welcome">
                    <h1>Bienvenido de <em>vuelta</em></h1>
                    <p>Ingresa para administrar tu negocio en Nicaragua.</p>
                </div>

                @if ($errors->any())
                    <div class="ui-alert ui-alert-error" style="margin-bottom: 1.25rem;">
                        <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form" data-loading>
                    @csrf

                    <div class="login-field">
                        <label class="form-label" for="login">Correo o nombre de usuario</label>
                        <input type="text"
                               id="login"
                               name="login"
                               class="input-field"
                               value="{{ old('login') }}"
                               placeholder="usuario@empresa.com"
                               required
                               autofocus
                               autocomplete="username">
                    </div>

                    <div class="login-field">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="login-password-wrap">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="input-field"
                                   placeholder="Tu contraseña"
                                   required
                                   autocomplete="current-password">
                            <button type="button" class="login-toggle-pw" id="toggle-password" aria-label="Mostrar contraseña">
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <label class="login-remember">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Recordarme</span>
                    </label>

                    <button type="submit" class="btn-primary login-submit">
                        Entrar a mi negocio
                    </button>
                </form>

                <p class="login-secure">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Tus datos están protegidos
                </p>

                <div class="login-footer" data-developer-credit>
                    <p>© {{ date('Y') }} {{ $developerName }} · Estelí, Nicaragua</p>
                    <p class="login-credit">{{ $systemName }} · Hecho en Nicaragua</p>
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app-ui.js') }}"></script>
    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            this.textContent = showing ? 'Mostrar' : 'Ocultar';
            this.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    </script>
</body>
</html>
