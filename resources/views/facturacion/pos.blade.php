@extends('layouts.app')
@section('hide_back', true)

@section('title', 'POS - Punto de Venta')
@section('hide-header', 'true')
@section('main-fluid', true)
@section('main-class', 'p-0')

@section('content')

<div id="posApp" class="pos-shell flex h-full min-h-0 flex-col overflow-hidden bg-slate-50 md:flex-row"
     data-products='@json($products)'
     data-clients='@json($clients)'
     data-categories='@json($categories)'
     data-default-tax-rate="{{ $defaultTaxRate }}"
     data-warehouses='@json($warehouses)'
     data-default-warehouse-id="{{ $defaultWarehouseId }}"
     data-product-search-url="{{ route('facturacion.pos-products') }}"
     data-credit-override-url="{{ route('facturacion.credit-override') }}"
     data-product-image-url="{{ url('/facturacion/pos/products') }}"
     data-daily-report-url="{{ route('facturacion.pos-daily-report') }}"
     data-company-currency="{{ $posReferenceFx['company_currency'] }}"
     data-company-symbol="{{ $posReferenceFx['company_symbol'] }}"
     data-reference-currency="{{ $posReferenceFx['reference_currency'] }}"
     data-reference-symbol="{{ $posReferenceFx['reference_symbol'] }}"
     data-reference-rate="{{ $posReferenceFx['reference_rate'] ?? '' }}">

    <input type="file" id="posProductImageInput" class="hidden" accept="image/jpeg,image/png,image/webp" capture="environment">

    {{-- COLUMNA IZQUIERDA: TICKET --}}
    <div class="pos-ticket-col min-h-0 w-full flex-[1.05] overflow-hidden border-b border-slate-200 bg-white md:h-full md:max-h-none md:min-w-[280px] md:max-w-[480px] md:w-[38%] md:flex-none md:border-b-0 md:border-r">

        {{-- Barra de acciones rápidas --}}
        <div class="px-4 py-2 bg-slate-800 text-white flex items-center justify-between text-xs shrink-0">
            <span class="font-semibold">Ticket #<span id="ticketNumber">1</span></span>
            <div class="flex gap-2">
                <button type="button" onclick="holdTicket()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg" title="F4 - Apartar">Apartar · F4</button>
                <button type="button" onclick="showHeldTickets()" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg" title="F6 - Recuperar ticket">
                    Recuperar <span id="heldCount" class="bg-white/20 px-1 rounded">0</span>
                </button>
            </div>
        </div>

        <div id="ticketScroller" class="min-h-0 overflow-y-auto border-b border-slate-200">
            <div id="ticketItems" class="divide-y divide-slate-100"></div>
            <div id="emptyTicket" class="flex h-full flex-col items-center justify-center py-12 text-slate-400">
                <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <p class="text-base font-medium text-slate-500">Ticket vacío</p>
                <p class="text-xs text-slate-400 mt-1">F2 buscar · F9 cobrar · F4 apartar</p>
            </div>
        </div>

        <div class="pos-ticket-totals shrink-0 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span id="orderDiscountLabel" class="hidden">Descuento</span>
                    <span id="discountDisplay" class="font-medium text-red-600 hidden">-C$ 0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span id="taxLabel">IVA ({{ number_format($defaultTaxRate * 100, 2) }}%)</span>
                    <span id="taxDisplay" class="font-medium">C$ 0.00</span>
                </div>
                <div class="flex items-end justify-between gap-2 border-t border-slate-300 pt-1.5">
                    <span class="text-xs font-semibold text-slate-700">Total</span>
                    <div class="text-right">
                        <span id="totalDisplay" class="block text-2xl font-bold leading-none text-slate-900">C$ 0.00</span>
                        <span id="totalReferenceDisplay" class="hidden text-[11px] font-semibold text-slate-500"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-ticket-actions space-y-2 p-3">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="clientBtn" onclick="openClientModal()"
                    class="flex min-w-0 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-2.5 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 sm:text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span id="clientDisplay" class="truncate">Cliente General</span>
                </button>

                <button type="button" onclick="openDiscountModal()"
                    class="flex min-w-0 items-center justify-center gap-1.5 rounded-xl bg-teal-600 px-2.5 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-teal-700 sm:text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span class="truncate">Descuento</span>
                </button>
            </div>

            @include('facturacion._numpad')
        </div>
    </div>

    {{-- COLUMNA DERECHA: PRODUCTOS Y PAGO --}}
    <div class="flex min-h-0 min-w-0 flex-1 flex-col bg-white">

        <div class="shrink-0 space-y-2 border-b border-slate-200 bg-white p-2.5 sm:p-3">
            <div class="flex items-center gap-2 overflow-x-auto text-[11px] text-slate-500" aria-label="Atajos de teclado del punto de venta">
                <button type="button" onclick="showShortcutHelp()" class="shrink-0 rounded-md bg-slate-800 px-2 py-1 font-semibold text-white" title="Ver todos los atajos">F1 Atajos</button>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 lg:inline"><b>F2</b> Buscar</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 lg:inline"><b>F3</b> Cliente</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 2xl:inline"><b>F4</b> Apartar</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 2xl:inline"><b>F6</b> Recuperar</span>
                <span class="hidden shrink-0 rounded-md bg-indigo-50 px-2 py-1 font-semibold text-indigo-700 lg:inline"><b>F9</b> Cobrar</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 2xl:inline"><b>F10</b> Corte</span>
                <div class="ml-auto hidden shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-gradient-to-r from-white to-teal-50 px-3 py-1 shadow-sm lg:flex"
                     data-pos-application-brand
                     aria-label="EsteliPOS, desarrollado por Northlink Microsystem">
                    <img src="{{ asset('images/northlink-logo-login.png') }}"
                         alt="Logo de {{ config('northlink.name') }}"
                         class="h-7 w-auto max-w-[6.5rem] object-contain">
                    <span class="h-6 w-px bg-slate-200" aria-hidden="true"></span>
                    <div>
                        <p class="text-sm font-black leading-none tracking-tight text-teal-700">{{ config('northlink.product') }}</p>
                        <p class="mt-0.5 text-[8px] font-bold uppercase leading-none tracking-[0.08em] text-slate-500">{{ config('northlink.name') }}</p>
                    </div>
                </div>
            </div>
            <div>
                <label for="productSearch" class="mb-1 hidden text-xs font-semibold text-slate-600 sm:block">Buscar producto</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                    <input type="search" id="productSearch" placeholder="Nombre o código de barras; Enter para agregar..."
                        class="input-with-leading-icon w-full rounded-xl border border-slate-300 py-2 pr-4 text-sm focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:py-2.5" autocomplete="off">
                </div>
            </div>
            <div class="flex min-w-0 flex-col gap-2 sm:flex-row">
                <select id="warehouseSelect" class="select-field min-w-0 text-sm sm:max-w-xs" title="Bodega de salida (opcional)">
                    <option value="" selected>Automática (según stock)</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}{{ $wh->is_default ? ' · Principal' : '' }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="applyOrderDiscount()" class="shrink-0 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-800 hover:bg-teal-100" title="Descuento global">% Descuento</button>
            </div>
            <p id="warehouseHint" class="hidden text-[11px] text-slate-500 xl:block">Si no eliges bodega, el sistema descuenta de la que tenga stock disponible.</p>
            <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-1"></div>
        </div>

        <div id="productsGrid" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-2 sm:p-3 lg:p-4">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5"></div>
        </div>

        <div class="pos-pay-dock shrink-0 space-y-2 border-t border-slate-200 bg-white p-3">
            <label class="block text-xs font-semibold text-slate-700 sm:text-sm">Método de Pago</label>
            <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                @foreach([
                    ['cash', 'Efectivo', 'Pago en efectivo', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['card', 'Tarjeta', 'Crédito / débito', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ['transfer', 'Transferencia', 'Bancaria', 'M8 7h12m0 0l-4-4m4 4l-4 4m0-6H4m6 4v12a3 3 0 003 3h6a3 3 0 003-3V11a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3z'],
                    ['credit', 'Crédito', 'Cuenta cliente', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ] as [$method, $title, $sub, $icon])
                <button type="button" class="payment-method flex items-center gap-2 px-3 py-2 border-2 border-slate-200 rounded-xl text-left hover:border-slate-300 text-sm {{ $method === 'cash' ? 'border-indigo-600 bg-indigo-50' : '' }}"
                    data-method="{{ $method }}" @if($method === 'credit') id="creditMethodBtn" disabled @endif>
                    <div class="w-8 h-8 {{ $method === 'cash' ? 'bg-indigo-600' : 'bg-slate-200' }} rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 {{ $method === 'cash' ? 'text-white' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 truncate">{{ $title }}</p>
                        <p class="pos-pay-sub text-xs text-slate-500 truncate">{{ $sub }}</p>
                    </div>
                </button>
                @endforeach
            </div>

            <div id="creditLimitAlert" class="hidden rounded-xl border border-red-300 bg-red-50 p-3 text-red-900 shadow-sm" role="alert" aria-live="assertive">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                    <div>
                        <p class="text-sm font-bold">Límite de crédito sobrepasado</p>
                        <p class="mt-1 text-xs leading-5">Disponible: <strong id="creditAvailableAmount">C$ 0.00</strong> · Este ticket: <strong id="creditTicketAmount">C$ 0.00</strong> · Exceso: <strong id="creditExceededAmount">C$ 0.00</strong>.</p>
                        <p class="mt-1 text-xs">Reduce el ticket, registra un abono o selecciona otro método de pago.</p>
                        <button type="button" onclick="openCreditOverrideModal()" class="mt-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700">Solicitar autorización</button>
                    </div>
                </div>
            </div>

            <div id="creditOverrideApproved" class="hidden rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900" role="status" aria-live="polite">
                <strong>Exceso autorizado</strong>
                <span id="creditOverrideApprovedBy" class="block text-xs"></span>
            </div>

            <button type="button" id="payBtn" onclick="initiatePayment()"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-2.5 font-bold text-white shadow-lg transition-all hover:bg-indigo-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>PAGAR (F9)</span>
                <span id="payBtnAmount">C$ 0.00</span>
            </button>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="clearTicket()" class="bg-slate-400 hover:bg-slate-500 text-white font-semibold py-2 rounded-xl text-sm">Descartar</button>
                <div class="flex gap-2">
                    <button type="button" id="dailyReportBtn" class="bg-slate-600 hover:bg-slate-700 text-white font-semibold py-2 rounded-xl text-sm" title="F10 - Corte del día">Corte del Día</button>
                    <button type="button" id="closeCashBtn" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 rounded-xl text-sm" title="Cierre de Caja (Arqueo)">Cierre Caja</button>
                </div>
            </div>
        </div>
    </div>

    <div id="presentationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Presentación</p>
                    <p id="presentationModalName" class="font-bold text-slate-900"></p>
                    <p id="presentationModalStock" class="text-xs text-slate-500"></p>
                </div>
                <button type="button" onclick="closePresentationModal()" class="text-2xl leading-none text-slate-400">×</button>
            </div>
            <div id="presentationModalChoices" class="grid gap-2 p-4"></div>
        </div>
    </div>

    {{-- MODAL: Cliente --}}
    <div id="clientModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Seleccionar Cliente</h2>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4">
                <input type="text" id="clientSearch" placeholder="Buscar cliente..." class="input-field mb-3">
            </div>
            <div class="px-4 pb-4 space-y-2 max-h-72 overflow-y-auto">
                <button type="button" onclick="selectClient(null, 'Cliente General')"
                    class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm">
                    <p class="font-semibold text-slate-800">Cliente General</p>
                    <p class="text-xs text-slate-500">Contado - Sin crédito</p>
                </button>
                <div id="clientsList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t border-slate-200 space-y-2">
                <button type="button" onclick="openQuickClientModal()"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-xl flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Cliente Rápido
                </button>
                <button type="button" onclick="document.getElementById('clientModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Cliente Rápido --}}
    <div id="quickClientModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Cliente Rápido</h2>
                <button type="button" onclick="document.getElementById('quickClientModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="quickClientForm" action="{{ route('clientes.quick-store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre *</label>
                        <input type="text" name="name" required placeholder="Nombre del cliente" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Teléfono</label>
                        <input type="text" name="phone" placeholder="Opcional" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tipo</label>
                        <select name="client_type" class="input-field">
                            <option value="natural">Natural</option>
                            <option value="company">Empresa</option>
                        </select>
                    </div>
                    <div id="cedulaField">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Cédula</label>
                        <input type="text" name="cedula" placeholder="Opcional" class="input-field">
                    </div>
                    <div id="rucField" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">RUC</label>
                        <input type="text" name="ruc" placeholder="Opcional" class="input-field">
                    </div>
                    <div id="businessNameField" class="hidden">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre Empresa</label>
                        <input type="text" name="business_name" placeholder="Opcional" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Dirección</label>
                        <input type="text" name="address" placeholder="Opcional" class="input-field">
                    </div>
                </div>
                <div class="p-4 border-t border-slate-200 space-y-2">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-xl">Guardar y Seleccionar</button>
                    <button type="button" onclick="document.getElementById('quickClientModal').classList.add('hidden')"
                        class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Descuento de Factura --}}
    <div id="discountModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Descuento de Factura</h2>
                <button type="button" onclick="document.getElementById('discountModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                <div class="flex gap-2">
                    <button type="button" onclick="setDiscountType('percentage')" id="discountTypePercentage"
                        class="flex-1 rounded-xl border-2 border-teal-600 bg-teal-50 px-4 py-2 font-semibold text-teal-800">
                        Porcentaje %
                    </button>
                    <button type="button" onclick="setDiscountType('fixed')" id="discountTypeFixed"
                        class="flex-1 rounded-xl border-2 border-slate-200 px-4 py-2 font-semibold text-slate-600 hover:border-slate-300">
                        Monto Fijo C$
                    </button>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" id="discountInputLabel">Porcentaje de descuento</label>
                    <input type="number" id="discountValue" step="0.01" min="0" placeholder="0"
                        class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-center text-xl font-bold focus:border-teal-600 focus:outline-none">
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <div class="flex justify-between text-sm text-slate-600 mb-2">
                        <span>Subtotal actual:</span>
                        <span id="discountSubtotal" class="font-semibold">C$ 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600 mb-2">
                        <span>Descuento:</span>
                        <span id="discountAmount" class="font-semibold text-red-600">-C$ 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-slate-900 border-t border-slate-200 pt-2">
                        <span>Total con descuento:</span>
                        <span id="discountTotal">C$ 0.00</span>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-slate-200 space-y-2">
                <button type="button" onclick="applyInvoiceDiscount()"
                    class="w-full rounded-xl bg-teal-600 py-2 font-semibold text-white hover:bg-teal-700">Aplicar Descuento</button>
                <button type="button" onclick="removeInvoiceDiscount()"
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 rounded-xl">Eliminar Descuento</button>
                <button type="button" onclick="document.getElementById('discountModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Atajos de teclado --}}
    <div id="shortcutModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="shortcutModalTitle">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h2 id="shortcutModalTitle" class="text-lg font-bold text-slate-900">Atajos del Punto de Venta</h2>
                    <p class="text-xs text-slate-500 mt-1">En Mac puede ser necesario usar fn + la tecla F.</p>
                </div>
                <button type="button" onclick="closeModal('shortcutModal')" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar atajos">✕</button>
            </div>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 p-5 text-sm">
                <dt class="font-bold text-indigo-700">F2</dt><dd>Buscar producto o código de barras</dd>
                <dt class="font-bold text-indigo-700">F3</dt><dd>Seleccionar cliente</dd>
                <dt class="font-bold text-indigo-700">F4</dt><dd>Apartar el ticket actual</dd>
                <dt class="font-bold text-indigo-700">F6</dt><dd>Recuperar un ticket apartado</dd>
                <dt class="font-bold text-indigo-700">F8</dt><dd>Colocar monto exacto durante el cobro</dd>
                <dt class="font-bold text-indigo-700">F9</dt><dd>Abrir el cobro; pulsar nuevamente para confirmar</dd>
                <dt class="font-bold text-indigo-700">F10</dt><dd>Abrir el corte del día</dd>
                <dt class="font-bold text-indigo-700">Supr</dt><dd>Quitar el producto seleccionado</dd>
                <dt class="font-bold text-indigo-700">Esc</dt><dd>Cerrar la ventana activa</dd>
            </dl>
            <div class="p-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('shortcutModal')" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 rounded-xl">Entendido</button>
            </div>
        </div>
    </div>

    {{-- MODAL: Autorización administrativa de exceso de crédito --}}
    <div id="creditOverrideModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 p-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Autorización requerida</p>
                    <h2 class="text-lg font-bold text-slate-900">Permitir exceso de crédito</h2>
                </div>
                <button type="button" onclick="closeCreditOverrideModal()" class="text-slate-400 hover:text-slate-700" aria-label="Cerrar">✕</button>
            </div>
            <form id="creditOverrideForm" class="space-y-4 p-5">
                <div class="rounded-xl bg-red-50 p-3 text-sm text-red-900">
                    <p>Cliente: <strong id="creditOverrideClientName"></strong></p>
                    <p>Ticket: <strong id="creditOverrideTicketTotal"></strong> · Disponible: <strong id="creditOverrideAvailable"></strong></p>
                </div>
                <div>
                    <label for="creditOverrideAdminLogin" class="mb-1 block text-sm font-semibold text-slate-700">Usuario o correo del administrador</label>
                    <input id="creditOverrideAdminLogin" class="input-field" type="text" required autocomplete="username">
                </div>
                <div>
                    <label for="creditOverridePassword" class="mb-1 block text-sm font-semibold text-slate-700">Contraseña del administrador</label>
                    <input id="creditOverridePassword" class="input-field" type="password" required autocomplete="current-password">
                </div>
                <p id="creditOverrideError" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert"></p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" onclick="closeCreditOverrideModal()" class="btn-outline justify-center">Cancelar</button>
                    <button id="creditOverrideSubmit" type="submit" class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Autorizar una vez</button>
                </div>
                <p class="text-xs leading-5 text-slate-500">La autorización dura 5 minutos, solo sirve para este cajero, cliente y monto, y se consume al guardar la venta.</p>
            </form>
        </div>
    </div>

    {{-- MODAL: Pago --}}
    <div id="paymentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white">
                <h2 class="text-lg font-bold text-slate-900">Procesar Pago</h2>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p id="paymentMethodDisplay" class="text-sm text-slate-600 px-5 pt-3"></p>

            <form id="paymentForm" action="{{ route('facturacion.pos-store') }}" method="POST">
                @csrf
                <div class="p-5 space-y-4">
                    <div class="text-center">
                        <p class="text-sm text-slate-600">Total a cobrar</p>
                        <p class="text-4xl font-bold text-indigo-600" id="paymentTotalDisplay">C$ 0.00</p>
                    </div>

                    <div id="cashSection" class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700">Monto recibido</label>
                        <input type="number" id="amountReceived" step="0.01" placeholder="0.00"
                            class="w-full px-4 py-3 text-2xl font-bold border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none text-center bg-slate-50">
                        <p class="text-sm text-slate-600 text-center">Cambio: <span id="changeDisplay" class="font-bold text-slate-900">C$ 0.00</span></p>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([10, 20, 50, 100, 200, 500, 1000] as $bill)
                            <button type="button" onclick="addBill({{ $bill }})" class="py-2 bg-slate-100 rounded-xl text-xs font-bold hover:bg-indigo-100 hover:text-indigo-700">C$ {{ $bill }}</button>
                            @endforeach
                            <button type="button" onclick="setExactAmount()" class="py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700">Exacto</button>
                        </div>
                    </div>

                    <div id="transferSection" class="space-y-2 hidden">
                        <label class="text-sm font-semibold text-slate-700">Número de referencia</label>
                        <input type="text" id="referenceNumber" placeholder="Ej: 123456789" class="input-field">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">Notas (opcional)</label>
                        <textarea id="saleNotes" rows="2" placeholder="Observaciones..." class="input-field resize-none"></textarea>
                    </div>
                </div>

                <input type="hidden" name="warehouse_id" id="warehouseIdInput">
                <input type="hidden" name="payment_type" id="paymentTypeInput">
                <input type="hidden" name="client_id" id="clientIdInput">
                <input type="hidden" name="items" id="itemsInput" value="[]">
                <input type="hidden" name="notes" id="notesInput">
                <input type="hidden" name="amount_received" id="amountReceivedInput">
                <input type="hidden" name="reference_number" id="referenceNumberInput">
                <input type="hidden" name="order_discount_pct" id="orderDiscountPctInput" value="0">
                <input type="hidden" name="credit_override_token" id="creditOverrideTokenInput">
                <input type="hidden" name="request_token" id="requestTokenInput" value="{{ (string) Str::uuid() }}">

                <div class="p-4 border-t border-slate-200 space-y-2 sticky bottom-0 bg-white">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl">Confirmar Pago</button>
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Tickets apartados --}}
    <div id="heldModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Tickets Apartados</h2>
                <button type="button" onclick="document.getElementById('heldModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="heldTicketsList" class="p-4 space-y-2 max-h-80 overflow-y-auto"></div>
        </div>
    </div>

    {{-- MODAL: Corte del día --}}
    <div id="dailyReportModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900">Corte de Caja del Día</h2>
                <button type="button" onclick="document.getElementById('dailyReportModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="dailyReportContent" class="p-5 space-y-4">
                <p class="text-slate-500 text-center">Cargando...</p>
            </div>
            <div class="p-4 border-t border-slate-200">
                <button type="button" onclick="window.open('{{ route('facturacion.index') }}?date=' + new Date().toISOString().split('T')[0], '_blank')"
                    class="w-full btn-primary justify-center">Ver detalle de ventas</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const app = document.getElementById('posApp');
    const normalizeProduct = p => {
        const defaultUnit = (p.sale_units || []).find(u => u.is_default) || (p.sale_units || [])[0];
        const totalStock = parseFloat(p.total_stock ?? p.stock ?? 0);
        const warehouseStock = parseFloat(p.warehouse_stock ?? 0);
        return {
        id: p.id,
        code: p.code ?? '',
        name: p.name,
        price: parseFloat(defaultUnit?.price ?? p.sale_price ?? 0),
        discount_pct: parseFloat(p.discount_pct ?? 0),
        discount_label: p.discount_label ?? '',
        stock: totalStock,
        total_stock: totalStock,
        warehouse_stock: warehouseStock,
        preferred_warehouse_id: p.preferred_warehouse_id ? parseInt(p.preferred_warehouse_id, 10) : null,
        preferred_warehouse_name: p.preferred_warehouse_name ?? null,
        stocks_by_warehouse: p.stocks_by_warehouse || [],
        base_unit_id: p.base_unit_id ?? null,
        base_unit_label: p.base_unit_label ?? 'und',
        unit_id: defaultUnit?.id ?? p.default_unit_id ?? p.base_unit_id ?? null,
        unit_label: defaultUnit?.abbreviation ?? p.default_unit_label ?? 'und',
        sale_units: (p.sale_units || []).map(u => ({
            id: u.id,
            abbreviation: u.abbreviation,
            name: u.name,
            factor_to_base: parseFloat(u.factor_to_base ?? 1) || 1,
            price: parseFloat(u.price ?? 0),
            price_breaks: (u.price_breaks || []).map(tier => ({
                min_quantity: parseFloat(tier.min_quantity),
                price: parseFloat(tier.price),
            })),
            stock: parseFloat(u.stock ?? 0),
            is_default: !!u.is_default,
        })),
        category_id: p.category_id,
        category_name: p.category?.name ?? 'Sin categoría',
        image_url: p.image_url ?? null,
        tax_rate: parseFloat(p.effective_tax_rate ?? app.dataset.defaultTaxRate ?? 0),
    };};
    let products = JSON.parse(app.dataset.products).map(normalizeProduct);
    const warehousesData = JSON.parse(app.dataset.warehouses || '[]');
    let selectedWarehouseId = null; // null = automática
    const warehouseSelectEl = document.getElementById('warehouseSelect');
    if (warehouseSelectEl?.value) {
        selectedWarehouseId = parseInt(warehouseSelectEl.value, 10);
    }
    const clientsData = JSON.parse(app.dataset.clients)
        .filter(c => c.code !== 'GEN')
        .map(c => ({
            id: c.id,
            name: c.name ?? '',
            business_name: c.business_name ?? '',
            client_type: c.client_type ?? 'natural',
            cedula: c.cedula ?? '',
            ruc: c.ruc ?? '',
            document_label: (c.client_type ?? 'natural') === 'company' ? 'RUC' : 'Cédula',
            document_number: (c.client_type ?? 'natural') === 'company' ? (c.ruc ?? '') : (c.cedula ?? ''),
            credit_enabled: !!c.credit_enabled,
            credit_limit: parseFloat(c.credit_limit ?? 0),
            credit_days: parseInt(c.credit_days ?? 30),
            price_list_id: c.price_list_id ?? null,
            price_list_name: c.price_list?.name ?? null,
            balance: parseFloat(c.balance ?? 0),
            available_credit: c.available_credit === null ? null : parseFloat(c.available_credit ?? 0),
            over_limit: !!c.over_limit,
        }));
    const categories = JSON.parse(app.dataset.categories);
    const dailyReportUrl = app.dataset.dailyReportUrl;

    let ticket = [];
    let currentClient = null;
    let selectedItemIndex = -1;
    let padBuffer = '';
    let currentCategory = 'all';
    let orderDiscountPct = 0;
    let ticketCounter = parseInt(localStorage.getItem('pos_ticket_counter') || '1');
    let currentPaymentMethod = 'cash';
    let creditOverrideToken = '';
    let creditOverrideMaximum = 0;
    let creditOverrideClientId = null;
    const HELD_KEY = 'pos_held_tickets';
    const modalIds = ['presentationModal', 'shortcutModal', 'clientModal', 'paymentModal', 'creditOverrideModal', 'heldModal', 'dailyReportModal'];

    document.getElementById('ticketNumber').textContent = ticketCounter;

    function ticketLineKey(productId, unitId) {
        return `${productId}:${unitId ?? 'base'}`;
    }

    function productUnit(product, unitId) {
        return (product.sale_units || []).find(u => u.id == unitId) || product.sale_units?.[0];
    }

    function tierPrice(unit, quantity) {
        const eligible = (unit?.price_breaks || [])
            .filter(tier => tier.min_quantity <= quantity)
            .sort((a, b) => b.min_quantity - a.min_quantity)[0];
        return eligible ? eligible.price : parseFloat(unit?.price ?? 0);
    }

    function nextTierHint(unit, quantity) {
        const next = (unit?.price_breaks || [])
            .filter(tier => tier.min_quantity > quantity)
            .sort((a, b) => a.min_quantity - b.min_quantity)[0];
        if (!next) return '';
        return `Agrega ${formatQty(next.min_quantity - quantity)} más y paga ${formatMoney(next.price)} c/u`;
    }

    function refreshTicketTierPrices() {
        ticket.forEach(item => {
            const product = products.find(candidate => candidate.id == item.product_id);
            const unit = product ? productUnit(product, item.unit_id) : null;
            if (unit) item.price = tierPrice(unit, parseFloat(item.quantity) || 1);
        });
    }

    window.cardUnitId = function(productId) {
        const select = document.querySelector(`[data-product-unit-select="${productId}"]`);
        return select?.value ? parseInt(select.value, 10) : null;
    };

    window.updateProductCardUnit = function(productId) {
        const product = products.find(p => p.id == productId);
        const unit = product ? productUnit(product, cardUnitId(productId)) : null;
        if (!product || !unit) return;
        const priceEl = document.querySelector(`[data-product-price="${productId}"]`);
        if (priceEl) {
            priceEl.innerHTML = `${formatMoney(unit.price)} <span class="text-[10px] font-semibold text-slate-500">/ ${unit.abbreviation}</span>`;
        }
    };

    function formatQty(value) {
        const number = parseFloat(value);
        if (!Number.isFinite(number)) return '0';
        return number.toLocaleString('es-NI', { maximumFractionDigits: 4 });
    }

    function unitFactor(unit) {
        const factor = parseFloat(unit?.factor_to_base);
        return factor > 0 ? factor : 1;
    }

    function committedBaseQty(productId, exceptIdx = null) {
        const product = products.find(p => p.id == productId);
        if (!product) return 0;
        return ticket.reduce((sum, item, idx) => {
            if (item.product_id != productId || idx === exceptIdx) return sum;
            return sum + (parseFloat(item.quantity) || 0) * unitFactor(productUnit(product, item.unit_id));
        }, 0);
    }

    function remainingBaseQty(product, exceptIdx = null) {
        return Math.max(0, parseFloat(product.total_stock ?? product.stock ?? 0) - committedBaseQty(product.id, exceptIdx));
    }

    function maxPresentationQty(product, unit, exceptIdx = null) {
        const factor = unitFactor(unit);
        return Math.floor((remainingBaseQty(product, exceptIdx) / factor) * 10000) / 10000;
    }

    let presentationTicketIdx = null;

    window.closePresentationModal = function() {
        presentationTicketIdx = null;
        document.getElementById('presentationModal')?.classList.add('hidden');
    };

    window.openPresentationModal = function(product, ticketIdx = null) {
        presentationTicketIdx = ticketIdx;
        const modal = document.getElementById('presentationModal');
        document.getElementById('presentationModalName').textContent = product.name;
        document.getElementById('presentationModalStock').textContent = `Hay ${formatQty(product.total_stock)} ${product.base_unit_label || 'und'}`;
        const selectedId = ticketIdx != null ? ticket[ticketIdx]?.unit_id : product.unit_id;
        const baseLabel = product.base_unit_label || 'und';
        document.getElementById('presentationModalChoices').innerHTML = (product.sale_units || []).map(unit => {
            const factor = unitFactor(unit);
            const available = maxPresentationQty(product, unit, ticketIdx);
            const factorHint = factor === 1 ? `1 ${baseLabel}` : `1 ${unit.abbreviation} = ${formatQty(factor)} ${baseLabel}`;
            const defaultBadge = unit.is_default ? '<span class="ml-1 rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-indigo-700">Predeterminada</span>' : '';
            return `
            <button type="button" onclick="choosePresentation(${product.id}, ${unit.id})"
                class="flex items-center justify-between rounded-xl border px-4 py-3 text-left ${selectedId == unit.id ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 hover:border-indigo-400'}">
                <span>
                    <span class="font-semibold text-slate-900">${unit.name}</span>${defaultBadge}
                    <span class="mt-0.5 block text-[11px] text-slate-500">${factorHint} · ${formatQty(available)} disponibles</span>
                </span>
                <span class="text-sm font-bold text-indigo-600">${formatMoney(unit.price)}</span>
            </button>`;
        }).join('');
        modal.classList.remove('hidden');
    };

    window.pickProductPresentation = function(productId, ticketIdx = null) {
        const product = products.find(p => p.id == productId);
        if (!product) return;
        if ((product.sale_units || []).length < 2) {
            if (ticketIdx == null) addProductToTicket(productId);
            return;
        }
        openPresentationModal(product, ticketIdx);
    };

    window.choosePresentation = function(productId, unitId) {
        if (presentationTicketIdx != null) {
            changeTicketUnit(presentationTicketIdx, unitId);
        } else {
            addProductToTicket(productId, 1, unitId);
        }
        closePresentationModal();
    };

    async function refreshCatalogPrices() {
        const params = new URLSearchParams({ search: '' });
        if (selectedWarehouseId) params.set('warehouse_id', selectedWarehouseId);
        if (currentClient) params.set('client_id', currentClient);
        try {
            const response = await fetch(`${app.dataset.productSearchUrl}?${params}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const remoteProducts = (await response.json()).map(normalizeProduct);
            products = remoteProducts;
            ticket.forEach(item => {
                const product = products.find(p => p.id == item.product_id);
                if (!product) return;
                const unit = productUnit(product, item.unit_id);
                if (unit) {
                    item.price = unit.price;
                    item.max_stock = maxPresentationQty(product, unit, ticket.indexOf(item));
                    item.unit_label = unit.abbreviation;
                }
            });
            renderProducts(document.getElementById('productSearch').value);
            renderTicket();
        } catch (error) {
            console.error('No se pudo actualizar precios', error);
        }
    }

    function syncWarehouseInput() {
        document.getElementById('warehouseIdInput').value = selectedWarehouseId || '';
        const hint = document.getElementById('warehouseHint');
        if (hint) {
            hint.textContent = selectedWarehouseId
                ? 'Filtro fijo: solo se muestran y venden existencias de la bodega seleccionada.'
                : 'Modo automático: el sistema descuenta de la bodega que tenga stock disponible.';
        }
    }

    document.getElementById('warehouseSelect')?.addEventListener('change', (e) => {
        selectedWarehouseId = e.target.value ? parseInt(e.target.value, 10) : null;
        syncWarehouseInput();
        refreshCatalogPrices();
    });
    syncWarehouseInput();

    function visibleModal() {
        return modalIds
            .map(id => document.getElementById(id))
            .find(modal => modal && !modal.classList.contains('hidden')) || null;
    }

    window.closeModal = function(id) {
        document.getElementById(id)?.classList.add('hidden');
    };

    window.showShortcutHelp = function() {
        document.getElementById('shortcutModal').classList.remove('hidden');
    };

    window.openClientModal = function() {
        document.getElementById('clientModal').classList.remove('hidden');
        window.setTimeout(() => document.getElementById('clientSearch')?.focus(), 0);
    };

    window.openQuickClientModal = function() {
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('quickClientModal').classList.remove('hidden');
        window.setTimeout(() => document.querySelector('#quickClientForm input[name="name"]')?.focus(), 0);
    };

    // Variables para descuento de factura
    let invoiceDiscountType = 'percentage'; // 'percentage' o 'fixed'
    let invoiceDiscountValue = 0;

    window.openDiscountModal = function() {
        const { subtotal } = getTotal();
        if (subtotal === 0) {
            alert('El ticket está vacío. Agrega productos antes de aplicar descuento.');
            return;
        }
        
        document.getElementById('clientModal').classList.add('hidden');
        document.getElementById('discountModal').classList.remove('hidden');
        
        // Resetear valores
        invoiceDiscountValue = 0;
        document.getElementById('discountValue').value = '';
        setDiscountType('percentage');
        updateDiscountPreview();
        
        window.setTimeout(() => document.getElementById('discountValue')?.focus(), 0);
    };

    window.setDiscountType = function(type) {
        invoiceDiscountType = type;
        
        const percentageBtn = document.getElementById('discountTypePercentage');
        const fixedBtn = document.getElementById('discountTypeFixed');
        const label = document.getElementById('discountInputLabel');
        
        if (type === 'percentage') {
            percentageBtn.classList.add('border-teal-600', 'bg-teal-50', 'text-teal-800');
            percentageBtn.classList.remove('border-slate-200', 'text-slate-600');
            fixedBtn.classList.remove('border-teal-600', 'bg-teal-50', 'text-teal-800');
            fixedBtn.classList.add('border-slate-200', 'text-slate-600');
            label.textContent = 'Porcentaje de descuento';
        } else {
            fixedBtn.classList.add('border-teal-600', 'bg-teal-50', 'text-teal-800');
            fixedBtn.classList.remove('border-slate-200', 'text-slate-600');
            percentageBtn.classList.remove('border-teal-600', 'bg-teal-50', 'text-teal-800');
            percentageBtn.classList.add('border-slate-200', 'text-slate-600');
            label.textContent = 'Monto de descuento (C$)';
        }
        
        updateDiscountPreview();
    };

    function updateDiscountPreview() {
        const { subtotal } = getTotal();
        const value = parseFloat(document.getElementById('discountValue').value) || 0;
        
        let discountAmount = 0;
        if (invoiceDiscountType === 'percentage') {
            discountAmount = subtotal * (value / 100);
        } else {
            discountAmount = Math.min(value, subtotal); // No puede ser mayor al subtotal
        }
        
        const totalWithDiscount = Math.max(0, subtotal - discountAmount);
        
        document.getElementById('discountSubtotal').textContent = formatMoney(subtotal);
        document.getElementById('discountAmount').textContent = '-' + formatMoney(discountAmount);
        document.getElementById('discountTotal').textContent = formatMoney(totalWithDiscount);
    }

    document.getElementById('discountValue')?.addEventListener('input', updateDiscountPreview);

    window.applyInvoiceDiscount = function() {
        const value = parseFloat(document.getElementById('discountValue').value) || 0;
        
        if (value <= 0) {
            alert('Ingresa un valor de descuento válido');
            return;
        }
        
        const { subtotal } = getTotal();
        
        if (invoiceDiscountType === 'percentage') {
            if (value > 100) {
                alert('El porcentaje no puede ser mayor a 100%');
                return;
            }
            orderDiscountPct = value;
        } else {
            if (value > subtotal) {
                alert('El descuento no puede ser mayor al subtotal');
                return;
            }
            orderDiscountPct = (value / subtotal) * 100;
        }
        
        updateTotals();
        document.getElementById('discountModal').classList.add('hidden');
        
        alert('Descuento aplicado correctamente');
    };

    window.removeInvoiceDiscount = function() {
        orderDiscountPct = 0;
        updateTotals();
        document.getElementById('discountModal').classList.add('hidden');
        alert('Descuento eliminado');
    };

    // Manejar cambio de tipo de cliente en modal rápido
    document.querySelector('#quickClientForm select[name="client_type"]')?.addEventListener('change', function(e) {
        const isCompany = e.target.value === 'company';
        document.getElementById('cedulaField').classList.toggle('hidden', isCompany);
        document.getElementById('rucField').classList.toggle('hidden', !isCompany);
        document.getElementById('businessNameField').classList.toggle('hidden', !isCompany);
    });

    // Manejar envío del formulario de cliente rápido
    document.getElementById('quickClientForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // Agregar el nuevo cliente a la lista local
                const newClient = {
                    id: data.client.id,
                    name: data.client.name,
                    business_name: data.client.business_name || '',
                    client_type: data.client.client_type || 'natural',
                    cedula: data.client.cedula || '',
                    ruc: data.client.ruc || '',
                    document_label: data.client.client_type === 'company' ? 'RUC' : 'Cédula',
                    document_number: data.client.client_type === 'company' ? (data.client.ruc || '') : (data.client.cedula || ''),
                    credit_enabled: false,
                    credit_limit: 0,
                    credit_days: 30,
                    balance: 0,
                    available_credit: 0,
                    over_limit: false,
                };
                clientsData.push(newClient);
                
                // Recargar la lista de clientes
                renderClientsList(document.getElementById('clientSearch').value);
                
                // Seleccionar el nuevo cliente
                selectClient(newClient.id, newClient.name);
                
                // Mostrar mensaje de éxito
                alert('Cliente agregado con éxito: ' + newClient.name);
                
                // Cerrar modal y limpiar formulario
                document.getElementById('quickClientModal').classList.add('hidden');
                this.reset();
            } else {
                alert(data.message || 'Error al crear cliente');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al crear cliente');
        }
    });

    // Cierre de caja button - abre la pantalla de arqueo
    const closeBtn = document.getElementById('closeCashBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(){
            // Abrir en la misma pestaña la vista de apertura/cierre
            window.location.href = '{{ route('arqueo.index', ['cerrar' => 1]) }}';
        });
    }

    const companySymbol = app.dataset.companySymbol || 'C$';
    const referenceSymbol = app.dataset.referenceSymbol || 'US$';
    const referenceCurrency = app.dataset.referenceCurrency || 'USD';
    const referenceRate = parseFloat(app.dataset.referenceRate || '0');

    function roundMoney(v) {
        const amount = parseFloat(v || 0);

        return Math.round((amount + Number.EPSILON) * 100) / 100;
    }

    function formatMoney(v) {
        return companySymbol + ' ' + roundMoney(v).toFixed(2);
    }

    function toReferenceAmount(v) {
        if (!(referenceRate > 0)) {
            return null;
        }

        return Math.round((parseFloat(v || 0) * referenceRate) * 100) / 100;
    }

    function formatReference(v) {
        const amount = toReferenceAmount(v);
        if (amount === null) {
            return '';
        }

        return referenceSymbol + ' ' + amount.toFixed(2);
    }

    function referenceLineHtml(v, className = 'text-[10px] font-medium text-slate-500') {
        const label = formatReference(v);
        if (!label) {
            return '';
        }

        return `<p class="${className}">${label} <span class="font-normal text-slate-400">${referenceCurrency}</span></p>`;
    }

    function lineSubtotal(item) {
        return item.price * item.quantity * (1 - item.discount / 100);
    }

    function itemTaxRate(item) {
        if (Number.isFinite(parseFloat(item.tax_rate))) return parseFloat(item.tax_rate);
        const product = products.find(p => p.id == item.product_id);
        return parseFloat(product?.tax_rate ?? app.dataset.defaultTaxRate ?? 0);
    }

    function getTotal() {
        const subtotal = ticket.reduce((sum, item) => sum + lineSubtotal(item), 0);
        const orderDiscount = subtotal * (orderDiscountPct / 100);
        const discountedLines = ticket.map(item => lineSubtotal(item) * (1 - orderDiscountPct / 100));
        const taxable = discountedLines.reduce((sum, value) => sum + value, 0);
        const tax = ticket.reduce((sum, item, index) => sum + discountedLines[index] * itemTaxRate(item), 0);
        return { subtotal, orderDiscount, tax, total: taxable + tax };
    }

    function updateTotals() {
        const { subtotal, orderDiscount, tax, total } = getTotal();
        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('taxDisplay').textContent = formatMoney(tax);
        document.getElementById('totalDisplay').textContent = formatMoney(total);
        document.getElementById('paymentTotalDisplay').textContent = formatMoney(total);
        document.getElementById('payBtnAmount').textContent = formatMoney(total);

        const totalReference = document.getElementById('totalReferenceDisplay');
        const referenceLabel = formatReference(total);
        if (totalReference) {
            if (referenceLabel) {
                totalReference.textContent = referenceLabel + ' ref.';
                totalReference.classList.remove('hidden');
            } else {
                totalReference.textContent = '';
                totalReference.classList.add('hidden');
            }
        }

        const rates = [...new Set(ticket.map(item => itemTaxRate(item).toFixed(4)))];
        const labelRate = rates.length === 1 ? `${(parseFloat(rates[0]) * 100).toFixed(2)}%` : (rates.length > 1 ? 'mixto' : `${(parseFloat(app.dataset.defaultTaxRate || 0) * 100).toFixed(2)}%`);
        document.getElementById('taxLabel').textContent = `IVA (${labelRate})`;

        const discLabel = document.getElementById('orderDiscountLabel');
        const discDisplay = document.getElementById('discountDisplay');
        if (orderDiscount > 0) {
            discLabel.classList.remove('hidden');
            discDisplay.classList.remove('hidden');
            discDisplay.textContent = '-' + formatMoney(orderDiscount) + ` (${orderDiscountPct.toFixed(1)}%)`;
        } else {
            discLabel.classList.add('hidden');
            discDisplay.classList.add('hidden');
        }

        updateCreditLimitAlert();
    }

    function updateCreditLimitAlert() {
        const alertBox = document.getElementById('creditLimitAlert');
        const payButton = document.getElementById('payBtn');
        const client = currentClient ? clientsData.find(c => c.id == currentClient) : null;
        const total = getTotal().total;
        const hasFiniteLimit = client && client.credit_enabled && client.credit_limit > 0;
        const available = hasFiniteLimit ? Math.max(0, parseFloat(client.available_credit ?? 0)) : null;
        const exceeded = currentPaymentMethod === 'credit' && available !== null && total > available + 0.0001;
        const authorized = exceeded
            && creditOverrideToken
            && String(creditOverrideClientId) === String(currentClient)
            && total <= creditOverrideMaximum + 0.01;

        alertBox.classList.toggle('hidden', !exceeded || authorized);
        document.getElementById('creditOverrideApproved').classList.toggle('hidden', !authorized);
        payButton.disabled = exceeded && !authorized;
        payButton.classList.toggle('opacity-50', exceeded && !authorized);
        payButton.classList.toggle('cursor-not-allowed', exceeded && !authorized);
        payButton.title = exceeded && !authorized ? 'La venta supera el crédito disponible del cliente.' : '';

        if (exceeded) {
            document.getElementById('creditAvailableAmount').textContent = formatMoney(available);
            document.getElementById('creditTicketAmount').textContent = formatMoney(total);
            document.getElementById('creditExceededAmount').textContent = formatMoney(total - available);
        }

        return exceeded && !authorized;
    }

    function clearCreditOverride() {
        creditOverrideToken = '';
        creditOverrideMaximum = 0;
        creditOverrideClientId = null;
        document.getElementById('creditOverrideTokenInput').value = '';
        document.getElementById('creditOverrideApproved').classList.add('hidden');
    }

    window.openCreditOverrideModal = function() {
        const client = clientsData.find(c => c.id == currentClient);
        if (!client) return;
        document.getElementById('creditOverrideClientName').textContent = client.name;
        document.getElementById('creditOverrideTicketTotal').textContent = formatMoney(getTotal().total);
        document.getElementById('creditOverrideAvailable').textContent = formatMoney(client.available_credit ?? 0);
        document.getElementById('creditOverrideError').classList.add('hidden');
        document.getElementById('creditOverridePassword').value = '';
        document.getElementById('creditOverrideModal').classList.remove('hidden');
        window.setTimeout(() => document.getElementById('creditOverrideAdminLogin').focus(), 0);
    };

    window.closeCreditOverrideModal = function() {
        document.getElementById('creditOverridePassword').value = '';
        document.getElementById('creditOverrideModal').classList.add('hidden');
    };

    document.getElementById('creditOverrideForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = document.getElementById('creditOverrideSubmit');
        const errorBox = document.getElementById('creditOverrideError');
        submit.disabled = true;
        submit.textContent = 'Verificando…';
        errorBox.classList.add('hidden');

        try {
            const response = await fetch(app.dataset.creditOverrideUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('#paymentForm input[name="_token"]').value,
                },
                body: JSON.stringify({
                    admin_login: document.getElementById('creditOverrideAdminLogin').value,
                    password: document.getElementById('creditOverridePassword').value,
                    client_id: currentClient,
                    amount: getTotal().total,
                }),
            });
            const data = await response.json();
            if (!response.ok) {
                const firstError = Object.values(data.errors || {}).flat()[0];
                throw new Error(firstError || data.message || 'No se pudo autorizar el exceso.');
            }

            creditOverrideToken = data.token;
            creditOverrideMaximum = getTotal().total;
            creditOverrideClientId = currentClient;
            document.getElementById('creditOverrideTokenInput').value = data.token;
            document.getElementById('creditOverrideApprovedBy').textContent = `Autorizado por ${data.administrator} para este ticket.`;
            closeCreditOverrideModal();
            updateCreditLimitAlert();
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
            document.getElementById('creditOverridePassword').value = '';
        } finally {
            submit.disabled = false;
            submit.textContent = 'Autorizar una vez';
        }
    });

    function revealTicketLine(index) {
        requestAnimationFrame(() => {
            const scroller = document.getElementById('ticketScroller');
            const row = scroller?.querySelector(`[data-ticket-idx="${index}"]`);
            if (!scroller || !row) {
                return;
            }

            row.classList.add('ticket-line--fresh');
            const rowBox = row.getBoundingClientRect();
            const viewBox = scroller.getBoundingClientRect();
            const padding = 8;
            if (rowBox.bottom > viewBox.bottom - padding) {
                scroller.scrollTop += rowBox.bottom - viewBox.bottom + padding;
            } else if (rowBox.top < viewBox.top + padding) {
                scroller.scrollTop -= viewBox.top - rowBox.top + padding;
            }
        });
    }

    function renderTicket() {
        const container = document.getElementById('ticketItems');
        const emptyMsg = document.getElementById('emptyTicket');

        if (ticket.length === 0) {
            container.innerHTML = '';
            emptyMsg.classList.remove('hidden');
            selectedItemIndex = -1;
            document.getElementById('selectedItemBar').classList.add('hidden');
            updateTotals();
            return;
        }

        emptyMsg.classList.add('hidden');
        refreshTicketTierPrices();
        container.innerHTML = ticket.map((item, idx) => `
            <div onclick="selectTicketItem(${idx})" data-ticket-idx="${idx}" class="p-3 cursor-pointer transition-colors group border-b border-slate-100 ${selectedItemIndex === idx ? 'bg-indigo-50 border-l-4 border-l-indigo-600' : 'hover:bg-slate-50'}">
                <div class="flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">${item.name}</p>
                        <div class="flex gap-2 text-xs text-slate-600 mt-1 flex-wrap">
                            <span>Cant: <b>${item.quantity}</b></span>
                            ${(() => {
                                const units = products.find(p => p.id == item.product_id)?.sale_units || [];
                                if (units.length < 2) return `<span>${item.unit_label || ''}</span>`;
                                return `<select onclick="event.stopPropagation()" onchange="event.stopPropagation(); changeTicketUnit(${idx}, this.value)"
                                    class="rounded border border-indigo-200 bg-indigo-50 px-1 py-0.5 text-xs font-semibold text-indigo-800" title="Presentación">
                                    ${units.map(unit => `<option value="${unit.id}" ${unit.id == item.unit_id ? 'selected' : ''}>${unit.abbreviation}</option>`).join('')}
                                </select>`;
                            })()}
                            <span>${formatMoney(item.price)}</span>
                            ${item.discount > 0 ? `<span class="text-red-600">-${item.discount}%</span>` : ''}
                            ${item.source_warehouse_name ? `<span class="text-indigo-600">${item.source_warehouse_name}</span>` : ''}
                        </div>
                        ${(() => {
                            const product = products.find(p => p.id == item.product_id);
                            const hint = nextTierHint(productUnit(product, item.unit_id), parseFloat(item.quantity) || 1);
                            return hint ? `<p class="mt-1 text-[11px] font-semibold text-emerald-700">${hint}</p>` : '';
                        })()}
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold text-slate-900 text-sm">${formatMoney(lineSubtotal(item))}</p>
                        ${referenceLineHtml(lineSubtotal(item), 'text-[10px] font-medium text-slate-500')}
                        <div class="flex gap-2 mt-1 justify-end">
                            <button type="button" onclick="event.stopPropagation(); applyDiscount(${idx})" class="text-xs text-indigo-600 hover:text-indigo-800">Dto.</button>
                            <button type="button" onclick="event.stopPropagation(); removeTicketItem(${idx})" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        if (selectedItemIndex >= 0 && ticket[selectedItemIndex]) {
            document.getElementById('selectedItemBar').classList.remove('hidden');
            document.getElementById('selectedItemName').textContent = ticket[selectedItemIndex].name;
            document.getElementById('selectedItemQty').textContent = ticket[selectedItemIndex].quantity;
        }
        updateTotals();
    }

    window.selectTicketItem = function(idx) {
        selectedItemIndex = idx;
        padBuffer = String(ticket[idx].quantity);
        renderTicket();
        expandPosPad();
    };

    window.addProductToTicket = function(productId, qty = 1, unitId = null) {
        const product = products.find(p => p.id == productId);
        if (!product) return;

        const unit = productUnit(product, unitId ?? product.unit_id);
        const resolvedUnitId = unit?.id ?? product.unit_id;
        const totalStock = parseFloat(product.total_stock ?? product.stock ?? 0);
        const unitPrice = parseFloat(unit?.price ?? product.price ?? 0);
        const baseLabel = product.base_unit_label || 'und';

        if (totalStock <= 0) {
            alert('Producto sin stock disponible en ninguna bodega');
            return;
        }

        const existingIdx = ticket.findIndex(item => ticketLineKey(item.product_id, item.unit_id) === ticketLineKey(productId, resolvedUnitId));
        const existing = existingIdx >= 0 ? ticket[existingIdx] : null;
        const newQty = (existing ? existing.quantity : 0) + qty;
        const maxQty = maxPresentationQty(product, unit, existingIdx >= 0 ? existingIdx : null);

        if (newQty > maxQty) {
            alert(`Stock insuficiente. Puedes vender ${formatQty(maxQty)} ${unit?.abbreviation || ''}. Hay ${formatQty(totalStock)} ${baseLabel} en total.`);
            return;
        }

        // Si hay stock en otra bodega, sincroniza preferencia (sin forzar al cajero).
        if (!selectedWarehouseId && product.preferred_warehouse_id) {
            // Mantener automático; el servidor elegirá la bodega con stock.
        } else if (selectedWarehouseId && product.warehouse_stock <= 0 && product.preferred_warehouse_id) {
            // Preferencia actual sin stock: no bloquear; avisar en el ticket.
        }

        if (existing) {
            existing.quantity = newQty;
            existing.price = tierPrice(unit, newQty);
            existing.max_stock = maxQty;
            existing.source_warehouse_id = product.preferred_warehouse_id || selectedWarehouseId;
            existing.source_warehouse_name = product.preferred_warehouse_name || null;
            renderTicket();
            revealTicketLine(existingIdx);
            return;
        }

        ticket.push({
            product_id: productId,
            unit_id: resolvedUnitId,
            unit_label: unit?.abbreviation ?? product.unit_label,
            name: product.name,
            price: tierPrice(unit, qty),
            quantity: qty,
            discount: product.discount_pct || 0,
            tax_rate: product.tax_rate,
            max_stock: maxQty,
            source_warehouse_id: product.preferred_warehouse_id || selectedWarehouseId,
            source_warehouse_name: product.preferred_warehouse_name || null,
        });
        renderTicket();
        revealTicketLine(ticket.length - 1);
    };

    window.changeTicketUnit = function(idx, unitId) {
        const item = ticket[idx];
        const product = products.find(p => p.id == item.product_id);
        if (!product) return;
        const unit = productUnit(product, parseInt(unitId, 10));
        if (!unit || item.unit_id == unit.id) return;

        const otherIdx = ticket.findIndex((row, rowIdx) =>
            rowIdx !== idx && ticketLineKey(row.product_id, unit.id) === ticketLineKey(item.product_id, unit.id)
        );

        if (otherIdx >= 0) {
            ticket[otherIdx].quantity += item.quantity;
            ticket.splice(idx, 1);
            const mergedIdx = otherIdx > idx ? otherIdx - 1 : otherIdx;
            const merged = ticket[mergedIdx];
            const maxQty = maxPresentationQty(product, unit, mergedIdx);
            merged.price = tierPrice(unit, merged.quantity);
            merged.unit_id = unit.id;
            merged.unit_label = unit.abbreviation;
            merged.max_stock = maxQty;
            if (merged.quantity > maxQty) merged.quantity = maxQty;
            selectedItemIndex = mergedIdx;
            renderTicket();
            return;
        }

        const maxQty = maxPresentationQty(product, unit, idx);
        item.unit_id = unit.id;
        item.unit_label = unit.abbreviation;
        item.price = tierPrice(unit, item.quantity);
        item.max_stock = maxQty;
        if (item.quantity > maxQty) item.quantity = maxQty;
        renderTicket();
    };

    window.applyDiscount = function(idx) {
        const discount = prompt('Porcentaje de descuento (0-100):', ticket[idx].discount || '0');
        if (discount !== null) {
            const v = parseFloat(discount);
            if (!isNaN(v) && v >= 0 && v <= 100) {
                ticket[idx].discount = v;
                renderTicket();
            }
        }
    };

    window.applyOrderDiscount = function() {
        const discount = prompt('Descuento global del ticket (%):', orderDiscountPct || '0');
        if (discount !== null) {
            const v = parseFloat(discount);
            if (!isNaN(v) && v >= 0 && v <= 100) {
                orderDiscountPct = v;
                updateTotals();
            }
        }
    };

    window.removeTicketItem = function(idx) {
        ticket.splice(idx, 1);
        selectedItemIndex = -1;
        padBuffer = '';
        renderTicket();
    };

    function setPosPadOpen(open) {
        const pad = document.getElementById('posNumpad');
        const toggle = document.getElementById('posPadToggle');
        if (!pad) return;
        pad.classList.toggle('pos-pad--open', open);
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && selectedItemIndex >= 0) {
            window.setTimeout(() => revealTicketLine(selectedItemIndex), 280);
        }
    }

    window.togglePosPad = function() {
        const pad = document.getElementById('posNumpad');
        if (!pad) return;
        setPosPadOpen(!pad.classList.contains('pos-pad--open'));
    };

    window.expandPosPad = function() {
        setPosPadOpen(true);
    };

    window.padInput = function(key) {
        if (selectedItemIndex < 0) {
            alert('Selecciona un producto del ticket para editar cantidad');
            return;
        }
        expandPosPad();
        if (padBuffer === '0' && key !== '.') padBuffer = key;
        else padBuffer += key;
        document.getElementById('selectedItemQty').textContent = padBuffer || '0';
    };

    window.padBackspace = function() {
        padBuffer = padBuffer.slice(0, -1);
        if (selectedItemIndex >= 0) {
            document.getElementById('selectedItemQty').textContent = padBuffer || '0';
        }
    };

    window.padAdjust = function(delta) {
        if (selectedItemIndex < 0) return;
        const item = ticket[selectedItemIndex];
        const product = products.find(p => p.id == item.product_id);
        const maxStock = maxPresentationQty(product, productUnit(product, item.unit_id), selectedItemIndex);
        const current = parseFloat(padBuffer || item.quantity) || 1;
        const next = Math.round((current + delta) * 100) / 100;
        if (next < 0.01) return;
        if (next > maxStock) {
            alert(`Stock máximo: ${formatQty(maxStock)} ${item.unit_label || ''} (hay ${formatQty(product?.total_stock ?? 0)} ${product?.base_unit_label || 'und'})`);
            return;
        }
        item.quantity = next;
        padBuffer = String(next);
        renderTicket();
    };

    window.padConfirm = function() {
        if (selectedItemIndex < 0) return;
        const qty = parseFloat(padBuffer) || 1;
        const item = ticket[selectedItemIndex];
        const product = products.find(p => p.id == item.product_id);
        const maxStock = maxPresentationQty(product, productUnit(product, item.unit_id), selectedItemIndex);

        if (qty <= 0) { alert('Cantidad inválida'); return; }
        if (qty > maxStock) {
            alert(`Stock máximo: ${formatQty(maxStock)} ${item.unit_label || ''} (hay ${formatQty(product?.total_stock ?? 0)} ${product?.base_unit_label || 'und'})`);
            return;
        }

        item.quantity = qty;
        padBuffer = '';
        renderTicket();
    };

    function renderProducts(filter = '') {
        const grid = document.querySelector('#productsGrid > div');
        let filtered = products;

        if (selectedWarehouseId) {
            filtered = filtered.filter(product => (product.stocks_by_warehouse || []).some(stock =>
                Number(stock.id) === Number(selectedWarehouseId) && parseFloat(stock.quantity || 0) > 0
            ));
        }

        if (currentCategory !== 'all') {
            filtered = filtered.filter(p => p.category_id == currentCategory);
        }
        if (filter.trim()) {
            const q = filter.toLowerCase();
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(q) ||
                (p.code && p.code.toLowerCase().includes(q))
            );
        }

        if (filtered.length === 0) {
            grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-8">No se encontraron productos</p>';
            return;
        }

        grid.innerHTML = filtered.map(p => {
            const totalStock = parseFloat(p.total_stock ?? p.stock ?? 0);
            const warehouseStock = parseFloat(p.warehouse_stock ?? 0);
            const availableStock = selectedWarehouseId ? warehouseStock : totalStock;
            const lowStock = availableStock > 0 && availableStock <= 5;
            const outStock = availableStock <= 0;
            const safeName = String(p.name).replace(/"/g, '&quot;');
            const imageBlock = p.image_url
                ? `<img src="${p.image_url}" alt="${safeName}" class="w-full h-full object-cover" referrerpolicy="no-referrer"
                        onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden')">
                   <div class="hidden w-full h-full flex flex-col items-center justify-center gap-1 text-slate-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-[10px]">Sin imagen</span>
                   </div>`
                : `<div class="w-full h-full flex flex-col items-center justify-center gap-1 text-slate-400 group-hover:text-indigo-400 transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-[10px]">Sin imagen</span>
                   </div>`;

            return `
            <div class="relative bg-white border-2 ${outStock ? 'border-slate-100 opacity-60' : 'border-slate-200 hover:border-indigo-500 hover:shadow-md'} rounded-xl overflow-hidden transition-all group">
                <button type="button" onclick="event.stopPropagation(); pickProductImage(${p.id})"
                    class="absolute right-1.5 top-1.5 z-10 rounded-md border border-slate-200 bg-white/95 p-1 text-slate-600 shadow-sm transition hover:border-indigo-600 hover:bg-indigo-600 hover:text-white"
                    title="${p.image_url ? 'Cambiar imagen' : 'Cargar imagen'}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </button>
                <button type="button" onclick="addProductToTicket(${p.id}, 1, cardUnitId(${p.id}))" ${outStock ? 'disabled' : ''}
                    class="w-full text-left ${outStock ? 'cursor-not-allowed' : ''}">
                    <div class="flex h-24 w-full items-center justify-center overflow-hidden border-b border-slate-100 bg-slate-100">
                        ${imageBlock}
                    </div>
                    <div class="p-2">
                        <p class="min-h-[2rem] line-clamp-2 text-xs font-semibold leading-4 text-slate-800">${p.name}</p>
                        <p class="mt-0.5 truncate text-[10px] text-slate-400">${p.code || '—'}</p>
                        <p data-product-price="${p.id}" class="mt-0.5 text-sm font-bold text-indigo-600">${formatMoney(p.price)} <span class="text-[10px] font-semibold text-slate-500">/ ${p.unit_label || 'und'}</span></p>
                        ${referenceLineHtml(p.price, 'text-[10px] font-semibold text-emerald-700')}
                        ${p.discount_pct > 0 ? `<p class="text-[10px] font-bold text-amber-600">${p.discount_label || p.discount_pct + '% OFF'} → ${formatMoney(p.price * (1 - p.discount_pct/100))}${formatReference(p.price * (1 - p.discount_pct/100)) ? ` · ${formatReference(p.price * (1 - p.discount_pct/100))}` : ''}</p>` : ''}
                        <p class="mt-0.5 text-[10px] ${outStock ? 'text-red-600 font-bold' : lowStock ? 'text-amber-600' : 'text-slate-500'}">
                            ${outStock
                                ? 'Sin stock'
                                : `Hay ${formatQty(availableStock)} ${p.base_unit_label || 'und'}${selectedWarehouseId ? ' en esta bodega' : ''}`}
                        </p>
                    </div>
                </button>
                ${(p.sale_units || []).length > 1 ? `<label class="block border-t border-indigo-100 bg-indigo-50 px-2 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-indigo-700">Presentación</span>
                    <select data-product-unit-select="${p.id}" ${outStock ? 'disabled' : ''}
                        onclick="event.stopPropagation()"
                        onchange="updateProductCardUnit(${p.id})"
                        class="mt-1 w-full rounded-lg border border-indigo-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-800">
                        ${(p.sale_units || []).map(unit => `<option value="${unit.id}" ${unit.is_default ? 'selected' : ''}>${unit.name || unit.abbreviation} · ${formatMoney(unit.price)}</option>`).join('')}
                    </select>
                </label>` : ''}
            </div>`;
        }).join('');
    }

    function renderCategoryTabs() {
        const tabs = document.getElementById('categoryTabs');
        let html = `<button type="button" onclick="setCategory('all')" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">Todos</button>`;
        categories.forEach(c => {
            html += `<button type="button" onclick="setCategory(${c.id})" class="category-tab px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap ${currentCategory == c.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${c.name}</button>`;
        });
        tabs.innerHTML = html;
    }

    window.setCategory = function(id) {
        currentCategory = id;
        renderCategoryTabs();
        renderProducts(document.getElementById('productSearch').value);
    };

    let pendingImageProductId = null;
    const productImageInput = document.getElementById('posProductImageInput');

    window.pickProductImage = function(productId) {
        pendingImageProductId = productId;
        productImageInput.value = '';
        productImageInput.click();
    };

    productImageInput?.addEventListener('change', async function () {
        const file = this.files?.[0];
        const productId = pendingImageProductId;
        pendingImageProductId = null;

        if (!file || !productId) return;

        if (file.size > 3 * 1024 * 1024) {
            alert('La imagen no puede superar 3 MB.');
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const uploadUrl = `${app.dataset.productImageUrl}/${productId}/image`;

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message
                    || (data.errors?.image ? data.errors.image[0] : null)
                    || 'No se pudo subir la imagen.';
                alert(message);
                return;
            }

            const index = products.findIndex(p => p.id == productId);
            if (index >= 0) {
                products[index].image_url = data.image_url;
            }

            renderProducts(document.getElementById('productSearch').value);
        } catch (error) {
            alert('Error de red al subir la imagen.');
        } finally {
            productImageInput.value = '';
        }
    });

    const searchInput = document.getElementById('productSearch');
    let productSearchTimer = null;
    let productSearchRequest = 0;
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        renderProducts(query);
        window.clearTimeout(productSearchTimer);
        if (query.length < 2) return;

        productSearchTimer = window.setTimeout(async () => {
            const requestId = ++productSearchRequest;
            const params = new URLSearchParams({ search: query });
            if (selectedWarehouseId) params.set('warehouse_id', selectedWarehouseId);
            if (currentClient) params.set('client_id', currentClient);
            if (currentCategory !== 'all') params.set('category_id', currentCategory);
            try {
                const response = await fetch(`${app.dataset.productSearchUrl}?${params}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) return;
                const remoteProducts = (await response.json()).map(normalizeProduct);
                if (requestId !== productSearchRequest) return;
                const known = new Map(products.map(product => [product.id, product]));
                remoteProducts.forEach(product => known.set(product.id, product));
                products = [...known.values()];
                renderProducts(query);
            } catch (error) {
                console.error('No se pudo consultar el catálogo', error);
            }
        }, 250);
    });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = e.target.value.trim();
            const exact = products.find(p => p.code && p.code.toLowerCase() === q.toLowerCase());
            if (exact) {
                addProductToTicket(exact.id);
                e.target.value = '';
                renderProducts();
            }
        }
    });

    window.selectClient = function(clientId, clientName) {
        if (String(currentClient) !== String(clientId)) clearCreditOverride();
        currentClient = clientId;
        document.getElementById('clientDisplay').textContent = clientName;
        document.getElementById('clientIdInput').value = clientId || '';

        const client = clientId ? clientsData.find(c => c.id == clientId) : null;
        const creditBtn = document.getElementById('creditMethodBtn');
        if (creditBtn) {
            const canCredit = client && client.credit_enabled;
            creditBtn.disabled = !canCredit;
            creditBtn.classList.toggle('opacity-50', !canCredit);
            if (canCredit && client.credit_limit > 0) {
                const avail = client.available_credit ?? 0;
                creditBtn.title = `Disponible: C$ ${parseFloat(avail).toFixed(2)} · Plazo: ${client.credit_days} días`;
            }
        }

        if (currentPaymentMethod === 'credit' && (!client || !client.credit_enabled)) {
            currentPaymentMethod = 'cash';
            updatePaymentMethodSelection();
        }

        updateCreditLimitAlert();

        refreshCatalogPrices();
        document.getElementById('clientModal').classList.add('hidden');
    };

    function renderClientsList(filter = '') {
        const container = document.getElementById('clientsList');
        const q = filter.toLowerCase();
        const filtered = clientsData.filter(c =>
            !q || c.name.toLowerCase().includes(q) || (c.business_name && c.business_name.toLowerCase().includes(q)) || (c.document_number && c.document_number.toLowerCase().includes(q))
        );
        container.innerHTML = filtered.map(c => `
            <button type="button" onclick="selectClient(${c.id}, '${c.name.replace(/'/g, "\\'")}')"
                class="w-full text-left px-4 py-3 hover:bg-slate-100 rounded-xl border border-slate-200 text-sm ${c.over_limit ? 'border-red-300 bg-red-50' : ''}">
                <div class="flex justify-between items-start">
                    <p class="font-semibold text-slate-800">${c.name}</p>
                    ${c.credit_enabled ? '<span class="badge-info text-xs">Crédito</span>' : ''}
                </div>
                <p class="text-xs text-slate-500 mt-1">${c.document_label}: ${c.document_number || '—'}</p>
                ${c.credit_enabled ? `<p class="text-xs text-slate-500 mt-1">Límite: ${c.credit_limit > 0 ? `C$ ${parseFloat(c.credit_limit).toFixed(2)}` : 'Ilimitado'} · Saldo: C$ ${parseFloat(c.balance || 0).toFixed(2)} · Disponible: ${c.available_credit === null ? 'Ilimitado' : `C$ ${parseFloat(c.available_credit || 0).toFixed(2)}`} · ${c.credit_days}d</p>` : '<p class="text-xs text-slate-400">Solo contado</p>'}
                ${c.price_list_name ? `<p class="text-xs text-indigo-600 mt-1">Lista: ${c.price_list_name}</p>` : ''}
            </button>
        `).join('');
    }

    document.getElementById('clientSearch')?.addEventListener('input', (e) => renderClientsList(e.target.value));

    function updatePaymentMethodSelection() {
        document.querySelectorAll('.payment-method').forEach(btn => {
            const active = btn.dataset.method === currentPaymentMethod;
            btn.classList.toggle('border-indigo-600', active);
            btn.classList.toggle('bg-indigo-50', active);
            btn.classList.toggle('border-slate-200', !active);
            const icon = btn.querySelector('div');
            if (icon) {
                icon.classList.toggle('bg-indigo-600', active);
                icon.classList.toggle('bg-slate-200', !active);
                const svg = icon.querySelector('svg');
                if (svg) {
                    svg.classList.toggle('text-white', active);
                    svg.classList.toggle('text-slate-600', !active);
                }
            }
        });
    }

    document.querySelectorAll('.payment-method').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            currentPaymentMethod = btn.dataset.method;
            updatePaymentMethodSelection();
            updateCreditLimitAlert();
        });
    });

    window.initiatePayment = function() {
        const { total } = getTotal();
        if (total === 0) { alert('El ticket está vacío'); return; }
        if (updateCreditLimitAlert()) return;

        document.getElementById('paymentTypeInput').value = currentPaymentMethod;
        document.getElementById('clientIdInput').value = currentClient || '';
        document.getElementById('cashSection').classList.toggle('hidden', currentPaymentMethod !== 'cash');
        document.getElementById('transferSection').classList.toggle('hidden', !['transfer', 'card'].includes(currentPaymentMethod));

        const names = { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia', credit: 'Crédito' };
        document.getElementById('paymentMethodDisplay').textContent = 'Método: ' + names[currentPaymentMethod];
        document.getElementById('paymentModal').classList.remove('hidden');

        if (currentPaymentMethod === 'cash') {
            document.getElementById('amountReceived').focus();
        }
    };

    window.addBill = function(amount) {
        const input = document.getElementById('amountReceived');
        input.value = ((parseFloat(input.value) || 0) + amount).toFixed(2);
        input.dispatchEvent(new Event('input'));
    };

    window.setExactAmount = function() {
        document.getElementById('amountReceived').value = getTotal().total.toFixed(2);
        document.getElementById('amountReceived').dispatchEvent(new Event('input'));
    };

    document.getElementById('amountReceived').addEventListener('input', (e) => {
        const amount = parseFloat(e.target.value) || 0;
        const change = amount - getTotal().total;
        document.getElementById('changeDisplay').textContent = formatMoney(change);
        document.getElementById('amountReceivedInput').value = amount;
    });

    document.getElementById('paymentForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const { total } = getTotal();
        const type = document.getElementById('paymentTypeInput').value;

        if (type === 'cash') {
            const amount = parseFloat(document.getElementById('amountReceived').value) || 0;
            if (amount < total) { alert('Monto recibido insuficiente'); return; }
        }
        if (['transfer', 'card'].includes(type)) {
            const ref = document.getElementById('referenceNumber').value.trim();
            if (!ref) { alert('Ingresa el número de referencia'); return; }
            document.getElementById('referenceNumberInput').value = ref;
        }

        let notes = document.getElementById('saleNotes').value;
        if (orderDiscountPct > 0) notes += (notes ? ' | ' : '') + `Descuento global: ${orderDiscountPct}%`;

        document.getElementById('itemsInput').value = JSON.stringify(ticket.map(item => ({
            product_id: item.product_id,
            unit_id: item.unit_id,
            quantity: item.quantity,
            price: item.price,
            discount: item.discount || 0,
        })));
        document.getElementById('warehouseIdInput').value = selectedWarehouseId;
        document.getElementById('notesInput').value = notes;
        document.getElementById('orderDiscountPctInput').value = orderDiscountPct;
        e.target.querySelector('button[type="submit"]').disabled = true;
        e.target.submit();
    });

    window.clearTicket = function() {
        if (ticket.length > 0 && !confirm('¿Descartar ticket actual?')) return;
        ticket = [];
        orderDiscountPct = 0;
        selectedItemIndex = -1;
        padBuffer = '';
        clearCreditOverride();
        searchInput.value = '';
        renderTicket();
        renderProducts();
    };

    function getHeldTickets() {
        return JSON.parse(localStorage.getItem(HELD_KEY) || '[]');
    }

    function updateHeldCount() {
        document.getElementById('heldCount').textContent = getHeldTickets().length;
    }

    window.holdTicket = function() {
        if (ticket.length === 0) { alert('No hay productos en el ticket'); return; }
        const held = getHeldTickets();
        held.push({
            id: Date.now(),
            label: `Ticket ${held.length + 1} - ${document.getElementById('clientDisplay').textContent}`,
            items: ticket,
            client: currentClient,
            clientName: document.getElementById('clientDisplay').textContent,
            orderDiscountPct,
            savedAt: new Date().toLocaleString(),
        });
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        ticket = [];
        orderDiscountPct = 0;
        currentClient = null;
        document.getElementById('clientDisplay').textContent = 'Cliente General';
        renderTicket();
        updateHeldCount();
        ticketCounter++;
        localStorage.setItem('pos_ticket_counter', ticketCounter);
        document.getElementById('ticketNumber').textContent = ticketCounter;
    };

    window.showHeldTickets = function() {
        const held = getHeldTickets();
        const list = document.getElementById('heldTicketsList');
        if (held.length === 0) {
            list.innerHTML = '<p class="text-slate-500 text-center py-4">No hay tickets apartados</p>';
        } else {
            list.innerHTML = held.map((h, idx) => `
                <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${h.label}</p>
                        <p class="text-xs text-slate-500">${h.items.length} productos · ${h.savedAt}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="resumeHeldTicket(${idx})" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs">Recuperar</button>
                        <button type="button" onclick="deleteHeldTicket(${idx})" class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs">Eliminar</button>
                    </div>
                </div>
            `).join('');
        }
        document.getElementById('heldModal').classList.remove('hidden');
    };

    window.resumeHeldTicket = function(idx) {
        const held = getHeldTickets();
        const saved = held[idx];
        if (!saved) return;
        if (ticket.length > 0 && !confirm('¿Reemplazar ticket actual?')) return;

        ticket = saved.items;
        currentClient = saved.client;
        orderDiscountPct = saved.orderDiscountPct || 0;
        document.getElementById('clientDisplay').textContent = saved.clientName;
        held.splice(idx, 1);
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        updateHeldCount();
        document.getElementById('heldModal').classList.add('hidden');
        renderTicket();
    };

    window.deleteHeldTicket = function(idx) {
        const held = getHeldTickets();
        held.splice(idx, 1);
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        updateHeldCount();
        showHeldTickets();
    };

    document.getElementById('dailyReportBtn').addEventListener('click', async () => {
        const modal = document.getElementById('dailyReportModal');
        const content = document.getElementById('dailyReportContent');
        modal.classList.remove('hidden');
        content.innerHTML = '<p class="text-slate-500 text-center">Cargando...</p>';

        try {
            const res = await fetch(dailyReportUrl);
            const data = await res.json();
            let paymentsHtml = '';
            Object.entries(data.by_payment).forEach(([type, row]) => {
                if (row.count > 0) {
                    paymentsHtml += `<div class="flex justify-between text-sm py-1"><span>${row.label} (${row.count})</span><span class="font-semibold">${formatMoney(row.total)}</span></div>`;
                }
            });

            content.innerHTML = `
                <div class="text-center mb-4">
                    <p class="text-xs text-slate-500">${data.date} · Cajero: ${data.cashier}</p>
                    <p class="text-4xl font-black text-indigo-600 mt-2">${formatMoney(data.total_sales)}</p>
                    <p class="text-sm text-slate-600">${data.invoice_count} ventas · Ticket prom: ${formatMoney(data.average_ticket)}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 space-y-1">
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Por método de pago</p>
                    ${paymentsHtml || '<p class="text-sm text-slate-400">Sin ventas hoy</p>'}
                </div>`;
        } catch {
            content.innerHTML = '<p class="text-red-600 text-center">Error al cargar el reporte</p>';
        }
    });

    document.addEventListener('keydown', (e) => {
        const supportedFunctionKeys = ['F1', 'F2', 'F3', 'F4', 'F6', 'F8', 'F9', 'F10'];
        const editingText = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
        const openModal = visibleModal();

        if (supportedFunctionKeys.includes(e.key)) {
            e.preventDefault();
            if (e.repeat) return;
        }

        if (e.key === 'F1') {
            if (openModal?.id === 'shortcutModal') closeModal('shortcutModal');
            else if (!openModal) showShortcutHelp();
            return;
        }

        if (e.key === 'F2') {
            if (!openModal) {
                searchInput.focus();
                searchInput.select();
            }
            return;
        }

        if (e.key === 'F3') {
            if (!openModal) openClientModal();
            return;
        }

        if (e.key === 'F4') {
            if (!openModal) holdTicket();
            return;
        }

        if (e.key === 'F6') {
            if (!openModal) showHeldTickets();
            return;
        }

        if (e.key === 'F8') {
            if (openModal?.id === 'paymentModal' && currentPaymentMethod === 'cash') setExactAmount();
            return;
        }

        if (e.key === 'F9') {
            if (openModal?.id === 'paymentModal') {
                document.getElementById('paymentForm').requestSubmit();
            } else if (!openModal) {
                initiatePayment();
            }
            return;
        }

        if (e.key === 'F10') {
            if (!openModal) document.getElementById('dailyReportBtn').click();
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            if (openModal) openModal.classList.add('hidden');
            else if (editingText) e.target.blur();
            return;
        }

        if (e.key === 'Delete' && !editingText && !openModal && selectedItemIndex >= 0) {
            e.preventDefault();
            removeTicketItem(selectedItemIndex);
        }
    });

    renderCategoryTabs();
    renderClientsList();
    renderProducts();
    renderTicket();
    updateHeldCount();
    updatePaymentMethodSelection();
});
</script>
@endpush

@endsection
