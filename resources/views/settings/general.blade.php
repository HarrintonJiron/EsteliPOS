@extends('layouts.app')

@section('title', 'Empresa y configuración general')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('settings.index') }}" class="hover:text-indigo-600">Configuración</a>
        <span aria-hidden="true">/</span>
        <span class="font-medium text-slate-800" aria-current="page">Empresa</span>
    </nav>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Empresa y configuración general</h1>
            <p class="page-subtitle">Identidad, localización y datos que aparecen en documentos comerciales.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20">
            <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
            Guardado en el sistema
        </span>
    </div>

    <form action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-dirty-form>
        @csrf

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Identidad de la empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Información comercial y legal que identifica el negocio.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="company_name" class="mb-1 block text-sm font-medium text-slate-700">Nombre comercial *</label>
                    <input id="company_name" type="text" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required maxlength="255" class="input-field" autocomplete="organization">
                    @error('company_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_legal_name" class="mb-1 block text-sm font-medium text-slate-700">Razón social</label>
                    <input id="company_legal_name" type="text" name="company_legal_name" value="{{ old('company_legal_name', $settings['company_legal_name']) }}" maxlength="255" class="input-field">
                    @error('company_legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_ruc" class="mb-1 block text-sm font-medium text-slate-700">RUC</label>
                    <input id="company_ruc" type="text" name="company_ruc" value="{{ old('company_ruc', $settings['company_ruc']) }}" maxlength="30" pattern="[A-Za-z0-9-]+" class="input-field">
                    @error('company_ruc')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="system_name" class="mb-1 block text-sm font-medium text-slate-700">Nombre visible del sistema *</label>
                    <input id="system_name" type="text" name="system_name" value="{{ old('system_name', $settings['system_name']) }}" required maxlength="100" class="input-field">
                    <p class="mt-1 text-xs text-slate-500">Se muestra en el menú lateral y títulos internos.</p>
                    @error('system_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Contacto y ubicación</h2>
                <p class="mt-1 text-sm text-slate-500">Datos utilizados en facturas, recibos y reportes.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="company_phone" class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
                    <input id="company_phone" type="tel" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}" maxlength="30" class="input-field" autocomplete="tel">
                    @error('company_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_email" class="mb-1 block text-sm font-medium text-slate-700">Correo</label>
                    <input id="company_email" type="email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}" maxlength="255" class="input-field" autocomplete="email">
                    @error('company_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="company_address" class="mb-1 block text-sm font-medium text-slate-700">Dirección</label>
                    <textarea id="company_address" name="company_address" rows="2" maxlength="500" class="input-field" autocomplete="street-address">{{ old('company_address', $settings['company_address']) }}</textarea>
                    @error('company_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_city" class="mb-1 block text-sm font-medium text-slate-700">Ciudad</label>
                    <input id="company_city" type="text" name="company_city" value="{{ old('company_city', $settings['company_city']) }}" maxlength="120" class="input-field" autocomplete="address-level2">
                    @error('company_city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_country" class="mb-1 block text-sm font-medium text-slate-700">País *</label>
                    <input id="company_country" type="text" name="company_country" value="{{ old('company_country', $settings['company_country']) }}" required maxlength="120" class="input-field" autocomplete="country-name">
                    @error('company_country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Localización y formatos</h2>
                <p class="mt-1 text-sm text-slate-500">Preferencias globales aplicadas a fechas, idioma y moneda.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3 sm:p-6">
                <div>
                    <label for="currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda *</label>
                    <select id="currency" name="currency" required class="select-field">
                        <option value="NIO" @selected(old('currency', $settings['currency']) === 'NIO')">Córdoba nicaragüense (NIO)</option>
                        <option value="USD" @selected(old('currency', $settings['currency']) === 'USD')">Dólar estadounidense (USD)</option>
                        <option value="EUR" @selected(old('currency', $settings['currency']) === 'EUR')">Euro (EUR)</option>
                    </select>
                    @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="currency_symbol" class="mb-1 block text-sm font-medium text-slate-700">Símbolo monetario *</label>
                    <input id="currency_symbol" type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required maxlength="5" class="input-field">
                    @error('currency_symbol')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="timezone" class="mb-1 block text-sm font-medium text-slate-700">Zona horaria *</label>
                    <select id="timezone" name="timezone" required class="select-field">
                        @foreach(['America/Managua', 'America/El_Salvador', 'America/Guatemala', 'America/Tegucigalpa', 'America/Costa_Rica'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $settings['timezone']) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    @error('timezone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_format" class="mb-1 block text-sm font-medium text-slate-700">Formato de fecha *</label>
                    <select id="date_format" name="date_format" required class="select-field">
                        <option value="d/m/Y" @selected(old('date_format', $settings['date_format']) === 'd/m/Y')">DD/MM/AAAA</option>
                        <option value="Y-m-d" @selected(old('date_format', $settings['date_format']) === 'Y-m-d')">AAAA-MM-DD</option>
                        <option value="m/d/Y" @selected(old('date_format', $settings['date_format']) === 'm/d/Y')">MM/DD/AAAA</option>
                    </select>
                    @error('date_format')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="language" class="mb-1 block text-sm font-medium text-slate-700">Idioma *</label>
                    <select id="language" name="language" required class="select-field">
                        <option value="es" @selected(old('language', $settings['language']) === 'es')">Español</option>
                        <option value="en" @selected(old('language', $settings['language']) === 'en')">English</option>
                    </select>
                    @error('language')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800 ring-1 ring-blue-600/20">
                    <p class="font-semibold">Tipos de cambio</p>
                    <p class="mt-1 text-xs leading-5">Configure las tasas de conversión entre monedas.</p>
                    <a href="{{ route('settings.exchange-rates.index') }}" class="mt-2 inline-block text-xs font-semibold underline">Gestionar tipos de cambio</a>
                </div>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Logos</h2>
                <p class="mt-1 text-sm text-slate-500">Se guardan de forma segura y se utilizan según el tipo de documento.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2 sm:p-6">
                <x-settings.image-upload name="company_logo" label="Logo principal" :current-url="$settings['company_logo_url']" help="Recomendado: fondo transparente y formato horizontal." max="2 MB" remove-name="remove_company_logo" />
                <x-settings.image-upload name="ticket_logo" label="Logo para tickets" :current-url="$settings['ticket_logo_url']" help="Recomendado: alto contraste para impresión térmica." max="1 MB" remove-name="remove_ticket_logo" />
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Mensajes de documentos</h2>
                <p class="mt-1 text-sm text-slate-500">Textos globales para facturas, recibos y tickets de reparación.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2 sm:p-6">
                <div>
                    <label for="invoice_footer" class="mb-1 block text-sm font-medium text-slate-700">Pie de factura</label>
                    <textarea id="invoice_footer" name="invoice_footer" rows="4" maxlength="1000" class="input-field" placeholder="Términos, garantía o información adicional…">{{ old('invoice_footer', $settings['invoice_footer']) }}</textarea>
                    @error('invoice_footer')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="receipt_message" class="mb-1 block text-sm font-medium text-slate-700">Mensaje para recibos</label>
                    <textarea id="receipt_message" name="receipt_message" rows="4" maxlength="500" class="input-field">{{ old('receipt_message', $settings['receipt_message']) }}</textarea>
                    @error('receipt_message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label for="repair_warranty_text" class="mb-1 block text-sm font-medium text-slate-700">Garantía predeterminada — reparaciones</label>
                    <textarea id="repair_warranty_text" name="repair_warranty_text" rows="4" maxlength="2000" class="input-field" placeholder="Texto que aparece en tickets de reparación de celulares…">{{ old('repair_warranty_text', $settings['repair_warranty_text']) }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Se carga automáticamente al crear una orden de reparación y se usa en el ticket cuando no hay texto personalizado.</p>
                    @error('repair_warranty_text')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 z-20 flex flex-col-reverse gap-3 rounded-xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <p class="hidden text-sm text-amber-700" data-unsaved-indicator>Hay cambios sin guardar.</p>
            <div class="flex gap-3 sm:ml-auto">
                <a href="{{ route('settings.index') }}" class="btn-outline flex-1 justify-center sm:flex-none" data-cancel-link>Cancelar</a>
                <button type="submit" class="btn-primary flex-1 justify-center sm:flex-none">Guardar cambios</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.querySelector('[data-dirty-form]');
        const indicator = document.querySelector('[data-unsaved-indicator]');
        let dirty = false;

        form?.addEventListener('input', () => {
            dirty = true;
            indicator?.classList.remove('hidden');
        });
        form?.addEventListener('change', () => {
            dirty = true;
            indicator?.classList.remove('hidden');
        });
        form?.addEventListener('submit', () => dirty = false);
        document.querySelector('[data-cancel-link]')?.addEventListener('click', () => dirty = false);
        window.addEventListener('beforeunload', event => {
            if (!dirty) return;
            event.preventDefault();
            event.returnValue = '';
        });
    })();
</script>
@endpush
