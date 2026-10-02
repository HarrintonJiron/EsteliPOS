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
     data-trade-in-lookup-url="{{ url('/facturacion/pos/trade-in/imei') }}"
    data-pos-exchange-rate-url="{{ route('facturacion.pos-exchange-rates.store') }}"
    data-pos-exchange-rate-id="{{ $posExchangeRate?->id }}"
    data-pos-exchange-rate="{{ $posExchangeRate?->rate }}"
     data-daily-report-url="{{ route('facturacion.pos-daily-report') }}"
    data-company-currency="{{ $companyCurrency }}"
    data-company-symbol="{{ $currencySymbol }}"
    data-reference-currency="{{ $posReferenceFx['reference_currency'] }}"
    data-reference-symbol="{{ $posReferenceFx['reference_symbol'] }}"
    data-reference-rate="{{ $posReferenceFx['reference_rate'] ?? '' }}">

    <nav class="pos-mobile-tabs" aria-label="Vista del punto de venta">
        <button type="button" class="pos-mobile-tab is-active" data-pos-mobile-view="catalog">Productos</button>
        <button type="button" class="pos-mobile-tab" data-pos-mobile-view="ticket">Ticket · <span id="mobileTicketTotal">{{ $currencySymbol }} 0.00</span></button>
    </nav>

    <input type="file" id="posProductImageInput" class="hidden" accept="image/jpeg,image/png,image/webp" capture="environment">

    {{-- COLUMNA IZQUIERDA: TICKET --}}
    <div class="pos-ticket-col min-h-0 w-full flex-[1.05] overflow-hidden border-b border-slate-200 bg-white md:h-full md:max-h-none md:min-w-[280px] md:max-w-[480px] md:w-[38%] md:flex-none md:border-b-0 md:border-r">

        {{-- Barra de acciones rápidas --}}
        <div class="flex shrink-0 items-center justify-between gap-2 bg-slate-800 px-4 py-2 text-xs text-white">
            <span class="shrink-0 font-semibold">Ticket #<span id="ticketNumber">1</span></span>
            <div class="flex gap-2">
                <button type="button" id="holdTicketBtn" onclick="holdTicket()" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded-lg" title="F4 · Deja esta factura en espera para atender a otro cliente y retomarla después">Dejar en espera · F4</button>
                <button type="button" id="heldTicketsBtn" onclick="showHeldTickets()" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded-lg transition" title="F6 · Ver las facturas en espera para retomar una y cobrarla">
                    En espera <span id="heldCount" class="bg-white/20 px-1 rounded">0</span>
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
                <p class="text-xs text-slate-400 mt-1">F2 buscar · F9 cobrar · F4 dejar en espera</p>
            </div>
        </div>

        <div class="pos-ticket-totals shrink-0 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-slate-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" class="font-medium">{{ $currencySymbol }} 0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span id="orderDiscountLabel" class="hidden">Descuento</span>
                    <span id="discountDisplay" class="font-medium text-red-600 hidden">-{{ $currencySymbol }} 0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-600">
                    <span id="taxLabel">IVA ({{ number_format($defaultTaxRate * 100, 2) }}%)</span>
                    <span id="taxDisplay" class="font-medium">{{ $currencySymbol }} 0.00</span>
                </div>
                <div class="flex items-end justify-between gap-2 border-t border-slate-300 pt-1.5">
                    <span class="text-xs font-semibold text-slate-700">Total</span>
                    <div class="text-right">
                        <span id="totalDisplay" class="block text-2xl font-bold leading-none text-slate-900">{{ $currencySymbol }} 0.00</span>
                        <span id="totalCordobaDisplay" class="hidden text-xs font-semibold text-emerald-700"></span>
                        <span id="totalReferenceDisplay" class="hidden text-[11px] font-semibold text-slate-500"></span>
                    </div>
                </div>
                <div id="tradeInSummaryRow" class="hidden flex justify-between text-xs text-amber-700">
                    <span>Equipo recibido</span>
                    <span id="tradeInDisplay" class="font-medium">-{{ $currencySymbol }} 0.00</span>
                </div>
                <div id="amountDueRow" class="hidden flex items-center justify-between gap-2 border-t border-dashed border-slate-300 pt-1.5">
                    <span class="text-xs font-bold text-slate-700">Saldo a pagar</span>
                    <span id="amountDueDisplay" class="text-lg font-extrabold text-emerald-700">{{ $currencySymbol }} 0.00</span>
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

            <button type="button" onclick="openTradeInModal()"
                class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-amber-600 px-2.5 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-amber-700 sm:text-sm">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span class="truncate">Recibir equipo a cambio</span>
                <span id="tradeInCount" class="hidden rounded-full bg-white/25 px-1.5 text-[10px] font-bold">0</span>
            </button>

            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('apartados.create'))
            <button type="button" onclick="createReservationFromPOS()" class="flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9a2 2 0 012-2zm3 0V6a4 4 0 018 0v2"/></svg>
                Convertir ticket en apartado
            </button>
            @endif

            @include('facturacion._numpad')
        </div>
    </div>

    {{-- COLUMNA DERECHA: PRODUCTOS Y PAGO --}}
    <div class="pos-catalog-col flex min-h-0 min-w-0 flex-1 flex-col bg-white">

        <div class="shrink-0 space-y-2 border-b border-slate-200 bg-white p-2.5 sm:p-3">
            <div class="flex items-center gap-2 overflow-x-auto text-[11px] text-slate-500" aria-label="Atajos de teclado del punto de venta">
                <button type="button" onclick="showShortcutHelp()" class="shrink-0 rounded-md bg-slate-800 px-2 py-1 font-semibold text-white" title="Ver todos los atajos">F1 Atajos</button>
                @if(auth()->user()->isAdmin())
                    <button type="button" onclick="openPosExchangeRateModal()" class="shrink-0 rounded-md bg-teal-600 px-2 py-1 font-semibold text-white hover:bg-teal-700" title="Actualizar tasa C$ por USD">+ Tasa<span id="posExchangeRateDisplay">{{ $posExchangeRate ? ': C$ '.number_format((float) $posExchangeRate->rate, 4) : '' }}</span></button>
                @endif
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 lg:inline"><b>F2</b> Buscar</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 lg:inline"><b>F3</b> Cliente</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 2xl:inline"><b>F4</b> Dejar en espera</span>
                <span class="hidden shrink-0 rounded-md bg-slate-100 px-2 py-1 2xl:inline"><b>F6</b> Facturas en espera</span>
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
                    <option value="" @selected(! $defaultWarehouseId)>Automática (según stock)</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}"{!! (int) $defaultWarehouseId === (int) $wh->id ? ' selected' : '' !!}>{{ $wh->name }}{{ $wh->is_default ? ' · Principal' : '' }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="applyOrderDiscount()" class="shrink-0 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-800 hover:bg-teal-100" title="Descuento global">% Descuento</button>
            </div>
            <p id="warehouseHint" class="hidden text-[11px] text-slate-500 xl:block">Si no eliges bodega, el sistema descuenta de la que tenga stock disponible.</p>
            <div id="categoryTabs" class="flex gap-2 overflow-x-auto pb-1"></div>
        </div>

        <div id="productsGrid" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-1.5 sm:p-2">
            <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"></div>
        </div>

        <div class="pos-pay-dock shrink-0 space-y-2 border-t border-slate-200 bg-white p-3">
            <div id="posPaymentPad" class="pos-pad">
                <button type="button" id="posPaymentToggle" class="pos-pad__toggle" onclick="togglePaymentPad()" aria-expanded="false" aria-controls="posPaymentKeys">
                    <span class="pos-pad__icon" aria-hidden="true">
                        <svg id="paymentMethodIcon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <span class="pos-pad__toggle-copy">
                        <strong>Método de pago</strong>
                        <small id="paymentMethodSummary">Efectivo</small>
                    </span>
                    <svg class="pos-pad-chevron" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                    </svg>
                </button>

                <div class="pos-pad__collapse">
                <div id="posPaymentKeys" class="pos-pad__body">
                <div class="pos-pay-grid">
                @foreach([
                        ['cash', 'Efectivo', 'Pago en efectivo', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['card', 'Tarjeta', 'Crédito / débito', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['transfer', 'Transferencia', 'Bancaria', 'M8 7h12m0 0l-4-4m4 4l-4 4m0-6H4m6 4v12a3 3 0 003 3h6a3 3 0 003-3V11a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3z'],
                        ['credit', 'Crédito', 'Cuenta cliente', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ] as [$method, $title, $sub, $icon])
                    <button type="button" class="pos-pay-option {{ $method === 'cash' ? 'pos-pay-option--active' : '' }}"
                        data-method="{{ $method }}" onclick="selectPaymentMethod('{{ $method }}')"
                        @if($method === 'credit') id="creditMethodOption" disabled @endif>
                        <span class="pos-pay-option__icon" aria-hidden="true">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $title }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $sub }}</span>
                        </span>
                    </button>
                    @endforeach
                </div>
            </div>
            </div>
            </div>

            <div id="creditLimitAlert" class="hidden rounded-xl border border-red-300 bg-red-50 p-3 text-red-900 shadow-sm" role="alert" aria-live="assertive">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                    <div>
                        <p class="text-sm font-bold">Límite de crédito sobrepasado</p>
                        <p class="mt-1 text-xs leading-5">Disponible: <strong id="creditAvailableAmount">{{ $currencySymbol }} 0.00</strong> · Este ticket: <strong id="creditTicketAmount">{{ $currencySymbol }} 0.00</strong> · Exceso: <strong id="creditExceededAmount">{{ $currencySymbol }} 0.00</strong>.</p>
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
                <span id="payBtnAmount">{{ $currencySymbol }} 0.00</span>
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
                        Monto Fijo {{ $currencySymbol }}
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
                        <span id="discountSubtotal" class="font-semibold">{{ $currencySymbol }} 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600 mb-2">
                        <span>Descuento:</span>
                        <span id="discountAmount" class="font-semibold text-red-600">-{{ $currencySymbol }} 0.00</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-slate-900 border-t border-slate-200 pt-2">
                        <span>Total con descuento:</span>
                        <span id="discountTotal">{{ $currencySymbol }} 0.00</span>
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

    {{-- MODAL: Recibir equipo a cambio (trade-in) --}}
    <div id="tradeInModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recibir equipo a cambio</h2>
                    <p class="text-xs text-slate-500 mt-1">Se descuenta del total de esta venta como parte de pago.</p>
                </div>
                <button type="button" onclick="document.getElementById('tradeInModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">IMEI del equipo recibido</label>
                    <div class="flex gap-2">
                        <input type="text" id="tradeInImei" placeholder="Ej: 356938035643809" class="input-field flex-1" autocomplete="off">
                        <button type="button" onclick="lookupTradeInImei()" class="rounded-xl bg-slate-700 px-3 text-sm font-semibold text-white hover:bg-slate-800">Buscar</button>
                    </div>
                </div>
                <div id="tradeInNotice" class="hidden rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900"></div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Nombre del equipo</label>
                        <input type="text" id="tradeInName" placeholder="Ej: iPhone 11 64GB" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Marca</label>
                        <input type="text" id="tradeInBrand" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Modelo</label>
                        <input type="text" id="tradeInModel" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Color</label>
                        <input type="text" id="tradeInColor" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Batería (%)</label>
                        <input type="number" id="tradeInBattery" min="0" max="100" class="input-field w-full">
                    </div>
                    <div class="col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Categoría</label>
                        <select id="tradeInCategorySelect" class="select-field w-full">
                            <option value="">Selecciona categoría</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Valor recibido a cambio</label>
                        <input type="number" id="tradeInValue" step="0.01" min="0.01" placeholder="0.00" class="input-field w-full">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Precio de reventa</label>
                        <input type="number" id="tradeInSalePrice" step="0.01" min="0" placeholder="0.00" class="input-field w-full">
                    </div>
                </div>
                <p id="tradeInError" class="hidden text-sm font-medium text-red-600" role="alert"></p>
            </div>
            <div class="p-4 border-t border-slate-200 space-y-2">
                <button type="button" onclick="addTradeIn()"
                    class="w-full rounded-xl bg-amber-600 py-2 font-semibold text-white hover:bg-amber-700">Agregar equipo a la venta</button>
                <button type="button" onclick="document.getElementById('tradeInModal').classList.add('hidden')"
                    class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2 rounded-xl">Cancelar</button>
            </div>
            <div id="tradeInList" class="border-t border-slate-200 divide-y divide-slate-100"></div>
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
                <dt class="font-bold text-indigo-700">F4</dt><dd>Dejar la factura actual en espera para atender a otro cliente</dd>
                <dt class="font-bold text-indigo-700">F6</dt><dd>Ver las facturas en espera y retomar una para cobrarla</dd>
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

    <div id="exchangeRateModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" aria-hidden="true">
        <div class="w-full max-w-sm rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="exchangeRateModalTitle">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 id="exchangeRateModalTitle" class="text-lg font-bold text-slate-900">Tasa de cambio POS</h2>
                <button type="button" onclick="closePosExchangeRateModal()" class="rounded p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar">×</button>
            </div>
            <form id="posExchangeRateForm" class="space-y-4 p-5">
                <div>
                    <label for="posExchangeRateValue" class="mb-1 block text-sm font-semibold text-slate-700">Córdobas por US$ 1</label>
                    <input id="posExchangeRateValue" type="number" min="0.000001" max="999999.999999" step="0.000001" required inputmode="decimal" class="input-field" placeholder="Ej.: 36.500000">
                    <p class="mt-1 text-xs text-slate-500">Se aplica solo como referencia C$ en este Punto de Venta.</p>
                </div>
                <p id="posExchangeRateError" class="hidden text-sm font-medium text-red-600" role="alert"></p>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closePosExchangeRateModal()" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar tasa</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Descuento por producto --}}
    <div id="lineDiscountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl mx-4">
            <h2 class="text-lg font-bold text-slate-900">Descuento del producto</h2>
            <p id="lineDiscountProduct" class="mt-1 text-sm text-slate-600"></p>
            <label for="lineDiscountValue" class="mt-4 block text-sm font-semibold text-slate-700">Porcentaje (0 a 100)</label>
            <input id="lineDiscountValue" type="number" min="0" max="100" step="0.01" class="input-field mt-1" inputmode="decimal">
            <p id="lineDiscountError" class="mt-2 hidden text-sm font-medium text-red-600" role="alert"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="lineDiscountCancel" class="btn-outline">Cancelar</button>
                <button type="button" id="lineDiscountSave" class="btn-primary">Aplicar descuento</button>
            </div>
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
                        <p class="text-4xl font-bold text-indigo-600" id="paymentTotalDisplay">{{ $currencySymbol }} 0.00</p>
                    </div>

                    <div id="cashSection" class="space-y-3">
                        <label class="text-sm font-semibold text-slate-700">Monto recibido</label>
                        <input type="number" id="amountReceived" step="0.01" placeholder="0.00"
                            class="w-full px-4 py-3 text-2xl font-bold border-2 border-slate-200 rounded-xl focus:border-indigo-500 focus:outline-none text-center bg-slate-50">
                        <p class="text-sm text-slate-600 text-center">Cambio: <span id="changeDisplay" class="font-bold text-slate-900">{{ $currencySymbol }} 0.00</span></p>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([1, 2, 5, 10, 20, 50, 100] as $bill)
                            <button type="button" onclick="addBill({{ $bill }})" class="py-2 bg-slate-100 rounded-xl text-xs font-bold hover:bg-indigo-100 hover:text-indigo-700">{{ $currencySymbol }} {{ $bill }}</button>
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
                <input type="hidden" name="trade_ins" id="tradeInsInput" value="[]">
                <input type="hidden" name="notes" id="notesInput">
                <input type="hidden" name="amount_received" id="amountReceivedInput">
                <input type="hidden" name="exchange_rate_id" id="exchangeRateIdInput">
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

    {{-- MODAL: Facturas en espera --}}
    <div id="heldModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
            <div class="p-5 border-b border-slate-200 flex justify-between items-start gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Facturas en espera</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Facturas que dejaste a medias para atender a otro cliente. Retoma una para seguir agregando productos y cobrarla.</p>
                </div>
                <button type="button" onclick="document.getElementById('heldModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600" aria-label="Cerrar">✕</button>
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
        condition: p.condition ?? null,
        condition_label: p.condition_label ?? null,
        price: parseFloat(defaultUnit?.price ?? p.sale_price ?? 0),
        source_sale_price: parseFloat(p.source_sale_price ?? p.sale_price ?? 0),
        price_currency: p.price_currency ?? app.dataset.companyCurrency,
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
    let tradeIns = [];
    let tradeInLookupProduct = null;
    let currentClient = null;
    let selectedItemIndex = -1;
    let padBuffer = '';
    let quantityEditorOpen = false;
    let replaceQuantityOnNextInput = false;
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

    const mobilePosMedia = window.matchMedia('(max-width: 767px)');
    let mobilePosView = 'catalog';
    function syncMobilePosLayout() {
        if (!mobilePosMedia.matches) {
            app.classList.remove('pos-mobile-ticket');
            return;
        }
        const selected = mobilePosView;
        app.classList.toggle('pos-mobile-ticket', selected === 'ticket');
        document.querySelectorAll('[data-pos-mobile-view]').forEach(button => {
            button.classList.toggle('is-active', button.dataset.posMobileView === selected);
        });
        const ticketColumn = document.querySelector('.pos-ticket-col');
        const payDock = document.querySelector('.pos-ticket-actions');
        if (selected === 'ticket' && ticketColumn && payDock) ticketColumn.appendChild(payDock);
    }
    document.querySelectorAll('[data-pos-mobile-view]').forEach(button => button.addEventListener('click', () => {
        mobilePosView = button.dataset.posMobileView;
        syncMobilePosLayout();
    }));
    mobilePosMedia.addEventListener?.('change', () => syncMobilePosLayout());
    syncMobilePosLayout();

    function ticketLineKey(productId, unitId) {
        return `${productId}:${unitId ?? 'base'}`;
    }

    function productUnit(product, unitId) {
        return (product.sale_units || []).find(u => u.id == unitId) || product.sale_units?.[0];
    }

    function tierPrice(unit, quantity, fallbackPrice = 0) {
        const eligible = (unit?.price_breaks || [])
            .filter(tier => tier.min_quantity <= quantity)
            .sort((a, b) => b.min_quantity - a.min_quantity)[0];
        return eligible ? eligible.price : parseFloat(unit?.price ?? fallbackPrice ?? 0);
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
            if (product) item.price = tierPrice(unit, parseFloat(item.quantity) || 1, product.price);
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
        const cordobaPriceEl = document.querySelector(`[data-product-cordoba-price="${productId}"]`);
        if (cordobaPriceEl) {
            cordobaPriceEl.textContent = formatCordobas(unit.price);
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
                    item.price = tierPrice(unit, parseFloat(item.quantity) || 1, product.price);
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
            label.textContent = 'Monto de descuento ({{ $currencySymbol }})';
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

    function resetTradeInForm() {
        tradeInLookupProduct = null;
        document.getElementById('tradeInImei').value = '';
        document.getElementById('tradeInName').value = '';
        document.getElementById('tradeInBrand').value = '';
        document.getElementById('tradeInModel').value = '';
        document.getElementById('tradeInColor').value = '';
        document.getElementById('tradeInBattery').value = '';
        document.getElementById('tradeInCategorySelect').value = '';
        document.getElementById('tradeInValue').value = '';
        document.getElementById('tradeInSalePrice').value = '';
        document.getElementById('tradeInNotice').classList.add('hidden');
        document.getElementById('tradeInError').classList.add('hidden');
    }

    window.openTradeInModal = function() {
        resetTradeInForm();
        renderTradeInList();
        document.getElementById('tradeInModal').classList.remove('hidden');
        document.getElementById('tradeInImei').focus();
    };

    window.lookupTradeInImei = async function() {
        const imei = document.getElementById('tradeInImei').value.trim();
        const notice = document.getElementById('tradeInNotice');
        tradeInLookupProduct = null;
        if (!imei) { notice.classList.add('hidden'); return; }

        if (tradeIns.some(t => t.imei === imei)) {
            notice.textContent = 'Este IMEI ya fue agregado a esta venta.';
            notice.classList.remove('hidden');
            return;
        }

        try {
            const res = await fetch(`${app.dataset.tradeInLookupUrl}/${encodeURIComponent(imei)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.exists) {
                tradeInLookupProduct = data.product;
                const p = data.product;
                let msg = `Este equipo ya estuvo registrado: <strong>${escapeHeldText(p.name)}</strong>`;
                if (p.lastSale) {
                    msg += ` · Vendido en factura ${escapeHeldText(p.lastSale.invoice_number)} (${escapeHeldText(p.lastSale.date)})`;
                }
                msg += p.trashed ? ' · Actualmente dado de baja, se reactivará.' : '';
                msg += '. Se reconocerá como el mismo producto en lugar de crear uno nuevo.';
                notice.innerHTML = msg;
                notice.classList.remove('hidden');

                document.getElementById('tradeInName').value = p.name || '';
                document.getElementById('tradeInBrand').value = p.brand || '';
                document.getElementById('tradeInModel').value = p.model || '';
                document.getElementById('tradeInColor').value = p.color || '';
                if (p.battery_percentage !== null && p.battery_percentage !== undefined) {
                    document.getElementById('tradeInBattery').value = p.battery_percentage;
                }
            } else {
                notice.classList.add('hidden');
            }
        } catch (e) {
            notice.classList.add('hidden');
        }
    };

    document.getElementById('tradeInImei').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); lookupTradeInImei(); }
    });

    window.addTradeIn = function() {
        const errorEl = document.getElementById('tradeInError');
        errorEl.classList.add('hidden');

        const imei = document.getElementById('tradeInImei').value.trim();
        const name = document.getElementById('tradeInName').value.trim();
        const categoryId = document.getElementById('tradeInCategorySelect').value;
        const tradeInValue = parseFloat(document.getElementById('tradeInValue').value) || 0;
        const salePrice = parseFloat(document.getElementById('tradeInSalePrice').value) || 0;
        const battery = document.getElementById('tradeInBattery').value;

        if (!imei) { errorEl.textContent = 'Ingresa el IMEI del equipo.'; errorEl.classList.remove('hidden'); return; }
        if (!name) { errorEl.textContent = 'Ingresa el nombre del equipo.'; errorEl.classList.remove('hidden'); return; }
        if (!categoryId) { errorEl.textContent = 'Selecciona una categoría.'; errorEl.classList.remove('hidden'); return; }
        if (tradeInValue <= 0) { errorEl.textContent = 'El valor recibido debe ser mayor que cero.'; errorEl.classList.remove('hidden'); return; }
        if (tradeIns.some(t => t.imei === imei)) { errorEl.textContent = 'Ese IMEI ya fue agregado a esta venta.'; errorEl.classList.remove('hidden'); return; }
        if (tradeIns.length >= 10) { errorEl.textContent = 'Máximo 10 equipos recibidos por venta.'; errorEl.classList.remove('hidden'); return; }

        tradeIns.push({
            imei,
            name,
            brand: document.getElementById('tradeInBrand').value.trim() || null,
            model: document.getElementById('tradeInModel').value.trim() || null,
            color: document.getElementById('tradeInColor').value.trim() || null,
            battery_percentage: battery !== '' ? parseInt(battery, 10) : null,
            category_id: parseInt(categoryId, 10),
            trade_in_value: roundMoney(tradeInValue),
            sale_price: roundMoney(salePrice),
            was_returning_phone: !!tradeInLookupProduct,
        });

        resetTradeInForm();
        renderTradeInList();
        updateTotals();
    };

    window.removeTradeIn = function(idx) {
        tradeIns.splice(idx, 1);
        renderTradeInList();
        updateTotals();
    };

    function renderTradeInList() {
        const list = document.getElementById('tradeInList');
        if (tradeIns.length === 0) { list.innerHTML = ''; return; }
        list.innerHTML = tradeIns.map((t, idx) => `
            <div class="flex items-center justify-between p-3 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-800">${escapeHeldText(t.name)}</p>
                    <p class="truncate text-xs text-slate-500">IMEI: ${escapeHeldText(t.imei)}${t.was_returning_phone ? ' · Reconocido' : ''}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="font-bold text-amber-700">-${formatMoney(t.trade_in_value)}</span>
                    <button type="button" onclick="removeTradeIn(${idx})" class="text-red-500 hover:text-red-700" aria-label="Quitar equipo">✕</button>
                </div>
            </div>
        `).join('');
    }

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

    const companyCurrency = app.dataset.companyCurrency || 'USD';
    const companySymbol = app.dataset.companySymbol || '{{ $currencySymbol }}';
    const referenceCurrency = app.dataset.referenceCurrency || '';
    const referenceSymbol = app.dataset.referenceSymbol || '';
    const referenceRate = parseFloat(app.dataset.referenceRate || '');
    const posExchangeRateUrl = app.dataset.posExchangeRateUrl;
    const posExchangeRateDisplay = document.getElementById('posExchangeRateDisplay');
    let posExchangeRate = {
        id: app.dataset.posExchangeRateId || null,
        rate: parseFloat(app.dataset.posExchangeRate || ''),
    };

    function roundMoney(v) {
        const amount = parseFloat(v || 0);

        return Math.round((amount + Number.EPSILON) * 100) / 100;
    }

    function formatMoney(v) {
        return companySymbol + ' ' + roundMoney(v).toFixed(2);
    }

    function formatReference(v) {
        return referenceCurrency && referenceSymbol && Number.isFinite(referenceRate) && referenceRate > 0
            ? `${referenceSymbol} ${roundMoney(v * referenceRate).toFixed(2)} ${referenceCurrency} ref.`
            : '';
    }

    function selectedCordobaRate() {
        return Number.isFinite(posExchangeRate.rate) && posExchangeRate.rate > 0 ? posExchangeRate.rate : null;
    }

    function formatCordobas(v) {
        const rate = selectedCordobaRate();
        return rate === null ? '' : `C$ ${roundMoney(v * rate).toFixed(2)}`;
    }

    window.openPosExchangeRateModal = function() {
        document.getElementById('posExchangeRateValue').value = selectedCordobaRate() || '';
        document.getElementById('exchangeRateModal').classList.remove('hidden');
        document.getElementById('posExchangeRateValue').focus();
    };

    window.closePosExchangeRateModal = function() {
        document.getElementById('exchangeRateModal').classList.add('hidden');
        document.getElementById('posExchangeRateError').classList.add('hidden');
    };

    document.getElementById('posExchangeRateForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = document.getElementById('posExchangeRateValue');
        const error = document.getElementById('posExchangeRateError');
        const button = event.target.querySelector('button[type="submit"]');
        button.disabled = true;

        try {
            const response = await fetch(posExchangeRateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: new URLSearchParams({ rate: input.value }),
            });
            const data = await response.json();

            if (!response.ok) {
                error.textContent = data.errors?.rate?.[0] || data.message || 'No se pudo guardar la tasa.';
                error.classList.remove('hidden');
                return;
            }

            posExchangeRate = { id: String(data.id), rate: Number(data.rate) };
            posExchangeRateDisplay.textContent = `: C$ ${posExchangeRate.rate.toFixed(4)}`;
            renderTicket();
            renderProducts(document.getElementById('productSearch').value);
            closePosExchangeRateModal();
            showToast('Tasa C$/USD actualizada para el POS.', 'success');
        } catch {
            error.textContent = 'No se pudo conectar para guardar la tasa.';
            error.classList.remove('hidden');
        } finally {
            button.disabled = false;
        }
    });

    function conditionBadgeHtml(condition, label, compact = false) {
        const styles = {
            new: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            used: 'border-amber-200 bg-amber-50 text-amber-700',
            open_box: 'border-sky-200 bg-sky-50 text-sky-700',
        };
        const labels = {
            new: 'Nuevo',
            used: 'Seminuevo',
            open_box: 'Open box',
        };

        if (!styles[condition]) return '';

        return `<span class="inline-flex items-center rounded-full border px-1.5 py-0.5 font-bold ${compact ? 'text-[8px]' : 'text-[10px]'} ${styles[condition]}">Estado: ${labels[condition] || label}</span>`;
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
        const lines = ticket.map(item => {
            const gross = item.price * item.quantity;
            const afterItemDiscount = gross * (1 - item.discount / 100);
            const orderDiscount = afterItemDiscount * (orderDiscountPct / 100);
            const net = roundMoney(afterItemDiscount - orderDiscount);
            const tax = roundMoney(net * itemTaxRate(item));

            return {
                discount: roundMoney((gross - afterItemDiscount) + orderDiscount),
                net,
                tax,
            };
        });
        const subtotal = roundMoney(lines.reduce((sum, line) => sum + line.net, 0));
        const orderDiscount = roundMoney(lines.reduce((sum, line) => sum + line.discount, 0));
        const tax = roundMoney(lines.reduce((sum, line) => sum + line.tax, 0));

        return { subtotal, orderDiscount, tax, total: roundMoney(subtotal + tax) };
    }

    function tradeInsTotal() {
        return roundMoney(tradeIns.reduce((sum, t) => sum + t.trade_in_value, 0));
    }

    function getAmountDue() {
        return Math.max(0, roundMoney(getTotal().total - tradeInsTotal()));
    }

    function updateTotals() {
        const { subtotal, orderDiscount, tax, total } = getTotal();
        const tradeInTotal = tradeInsTotal();
        const amountDue = getAmountDue();
        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('taxDisplay').textContent = formatMoney(tax);
        document.getElementById('totalDisplay').textContent = formatMoney(total);
        document.getElementById('paymentTotalDisplay').textContent = formatMoney(amountDue);
        document.getElementById('payBtnAmount').textContent = formatMoney(amountDue);
        document.getElementById('mobileTicketTotal').textContent = formatMoney(amountDue);
        const cordobaTotal = document.getElementById('totalCordobaDisplay');
        const cordobaLabel = formatCordobas(total);
        cordobaTotal.textContent = cordobaLabel;
        cordobaTotal.classList.toggle('hidden', cordobaLabel === '');
        const referenceTotal = document.getElementById('totalReferenceDisplay');
        const referenceLabel = formatReference(total);
        referenceTotal.textContent = referenceLabel;
        referenceTotal.classList.toggle('hidden', referenceLabel === '');

        const tradeInRow = document.getElementById('tradeInSummaryRow');
        const amountDueRow = document.getElementById('amountDueRow');
        if (tradeIns.length > 0) {
            tradeInRow.classList.remove('hidden');
            amountDueRow.classList.remove('hidden');
            document.getElementById('tradeInDisplay').textContent = '-' + formatMoney(tradeInTotal);
            document.getElementById('amountDueDisplay').textContent = formatMoney(amountDue);
        } else {
            tradeInRow.classList.add('hidden');
            amountDueRow.classList.add('hidden');
        }

        const tradeInCount = document.getElementById('tradeInCount');
        tradeInCount.textContent = tradeIns.length;
        tradeInCount.classList.toggle('hidden', tradeIns.length === 0);

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
        const total = getAmountDue();
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
                        <div class="flex items-center gap-1.5">
                            <p class="min-w-0 truncate text-sm font-semibold text-slate-900">${item.name}</p>
                            ${conditionBadgeHtml(item.condition, item.condition_label, true)}
                        </div>
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
                            ${formatCordobas(item.price) ? `<span class="font-semibold text-emerald-700">${formatCordobas(item.price)}</span>` : ''}
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
                        ${formatCordobas(lineSubtotal(item)) ? `<p class="text-[10px] font-semibold text-emerald-700">${formatCordobas(lineSubtotal(item))}</p>` : ''}
                        <div class="flex gap-2 mt-1 justify-end">
                            <button type="button" onclick="event.stopPropagation(); applyDiscount(${idx})" class="text-xs text-indigo-600 hover:text-indigo-800">Dto.</button>
                            <button type="button" onclick="event.stopPropagation(); removeTicketItem(${idx})" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        if (selectedItemIndex >= 0 && ticket[selectedItemIndex]) {
            document.getElementById('posQuantityTools').classList.toggle('hidden', !quantityEditorOpen);
            document.getElementById('selectedItemName').textContent = ticket[selectedItemIndex].name;
            document.getElementById('selectedItemQty').value = padBuffer || ticket[selectedItemIndex].quantity;
        }
        updateTotals();
    }

    window.selectTicketItem = function(idx) {
        selectedItemIndex = idx;
        padBuffer = String(ticket[idx].quantity);
        quantityEditorOpen = true;
        replaceQuantityOnNextInput = true;
        renderTicket();
        expandPosPad();
        focusQuantityInput();
    };

    window.addProductToTicket = function(productId, qty = 1, unitId = null, openQuantityEditor = false) {
        const shouldOpenQuantityEditor = openQuantityEditor && !mobilePosMedia.matches;
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
            existing.price = tierPrice(unit, newQty, unitPrice);
            existing.max_stock = maxQty;
            existing.source_warehouse_id = product.preferred_warehouse_id || selectedWarehouseId;
            existing.source_warehouse_name = product.preferred_warehouse_name || null;
            renderTicket();
            revealTicketLine(existingIdx);
            if (shouldOpenQuantityEditor) selectTicketItem(existingIdx);
            return;
        }

        ticket.push({
            product_id: productId,
            unit_id: resolvedUnitId,
            unit_label: unit?.abbreviation ?? product.unit_label,
            name: product.name,
            condition: product.condition,
            condition_label: product.condition_label,
            price: tierPrice(unit, qty, unitPrice),
            quantity: qty,
            discount: product.discount_pct || 0,
            tax_rate: product.tax_rate,
            max_stock: maxQty,
            source_warehouse_id: product.preferred_warehouse_id || selectedWarehouseId,
            source_warehouse_name: product.preferred_warehouse_name || null,
        });
        renderTicket();
        revealTicketLine(ticket.length - 1);
        if (shouldOpenQuantityEditor) selectTicketItem(ticket.length - 1);
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

    let discountTicketIndex = null;
    window.applyDiscount = function(idx) {
        discountTicketIndex = idx;
        document.getElementById('lineDiscountProduct').textContent = ticket[idx]?.name || 'Producto';
        document.getElementById('lineDiscountValue').value = ticket[idx]?.discount || 0;
        document.getElementById('lineDiscountError').classList.add('hidden');
        document.getElementById('lineDiscountModal').classList.remove('hidden');
        window.setTimeout(() => document.getElementById('lineDiscountValue').focus(), 0);
    };

    document.getElementById('lineDiscountCancel').addEventListener('click', () => {
        discountTicketIndex = null;
        document.getElementById('lineDiscountModal').classList.add('hidden');
    });

    document.getElementById('lineDiscountSave').addEventListener('click', () => {
        const value = parseFloat(document.getElementById('lineDiscountValue').value);
        const error = document.getElementById('lineDiscountError');
        if (!Number.isFinite(value) || value < 0 || value > 100) {
            error.textContent = 'Ingresa un porcentaje entre 0 y 100.';
            error.classList.remove('hidden');
            return;
        }
        if (discountTicketIndex !== null && ticket[discountTicketIndex]) {
            ticket[discountTicketIndex].discount = value;
        }
        discountTicketIndex = null;
        document.getElementById('lineDiscountModal').classList.add('hidden');
        renderTicket();
        showToast('Descuento actualizado.', 'success');
    });

    window.applyOrderDiscount = function() {
        openDiscountModal();
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

    function focusQuantityInput() {
        const quantityInput = document.getElementById('selectedItemQty');
        if (!quantityEditorOpen || !quantityInput) return;
        window.setTimeout(() => {
            quantityInput.focus({ preventScroll: true });
            quantityInput.setSelectionRange(0, quantityInput.value.length);
            const ticketColumn = document.querySelector('.pos-ticket-col');
            const editorBox = document.getElementById('posQuantityTools')?.getBoundingClientRect();
            const columnBox = ticketColumn?.getBoundingClientRect();
            const margin = 12;
            if (ticketColumn && editorBox && columnBox && editorBox.bottom > columnBox.bottom) {
                ticketColumn.scrollTop += editorBox.bottom - columnBox.bottom + margin;
            }
        }, 0);
    }

    window.hideQuantityEditor = function() {
        quantityEditorOpen = false;
        document.getElementById('posQuantityTools')?.classList.add('hidden');
        const pad = document.getElementById('posNumpad');
        const canOpen = quantityEditorOpen && selectedItemIndex >= 0 && !!ticket[selectedItemIndex];
        if (pad) pad.classList.toggle('hidden', !canOpen);
    };

    function applyTicketQuantity(idx, rawQuantity) {
        const item = ticket[idx];
        if (!item) return false;
        const qty = parseFloat(String(rawQuantity).replace(',', '.'));
        const product = products.find(p => p.id == item.product_id);
        const unit = productUnit(product, item.unit_id);
        const maxStock = maxPresentationQty(product, unit, idx);
        if (!Number.isFinite(qty) || qty <= 0) {
            focusQuantityInput();
            return false;
        }
        if (qty > maxStock) {
            alert(`Stock máximo: ${formatQty(maxStock)} ${item.unit_label || ''}`);
            focusQuantityInput();
            return false;
        }
        item.quantity = qty;
        item.price = tierPrice(unit, item.quantity, product?.price);
        padBuffer = String(qty);
        renderTicket();
        return true;
    }

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
        document.getElementById('selectedItemQty').value = padBuffer || '0';
    };

    window.padBackspace = function() {
        padBuffer = padBuffer.slice(0, -1);
        if (selectedItemIndex >= 0) {
            document.getElementById('selectedItemQty').value = padBuffer || '0';
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
        return applyTicketQuantity(selectedItemIndex, next);
    };

    window.padConfirm = function() {
        if (selectedItemIndex < 0) return false;
        return applyTicketQuantity(selectedItemIndex, padBuffer);
    };

    const quantityInput = document.getElementById('selectedItemQty');
    quantityInput?.addEventListener('input', (event) => {
        padBuffer = String(event.target.value).replace(',', '.');
        replaceQuantityOnNextInput = false;
    });
    quantityInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (padConfirm()) hideQuantityEditor();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            hideQuantityEditor();
        }
    });

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
                ? `<img src="${p.image_url}" alt="${safeName}" class="h-full w-full object-cover" referrerpolicy="no-referrer"
                        onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden')">
                   <div class="hidden flex h-full w-full items-center justify-center text-slate-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                   </div>`
                : `<div class="flex h-full w-full items-center justify-center text-slate-400 group-hover:text-indigo-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                   </div>`;

            return `
            <div class="group relative overflow-hidden rounded-lg border bg-white transition-all ${outStock ? 'border-slate-100 opacity-60' : 'border-slate-200 hover:border-indigo-500 hover:shadow-sm'}">
                <button type="button" onclick="event.stopPropagation(); pickProductImage(${p.id})"
                    class="absolute right-1 top-1 z-10 inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white/95 px-1.5 py-1 text-[9px] font-bold text-slate-700 shadow-sm transition hover:border-indigo-600 hover:bg-indigo-600 hover:text-white"
                    title="${p.image_url ? 'Cambiar imagen' : 'Agregar imagen'}" aria-label="${p.image_url ? 'Cambiar imagen de' : 'Agregar imagen a'} ${safeName}">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Foto</span>
                </button>
                <button type="button" onclick="addProductToTicket(${p.id}, 1, cardUnitId(${p.id}), true)" ${outStock ? 'disabled' : ''}
                    class="w-full text-left ${outStock ? 'cursor-not-allowed' : ''}">
                    <div class="flex h-14 w-full items-center justify-center overflow-hidden border-b border-slate-100 bg-slate-100">
                        ${imageBlock}
                    </div>
                    <div class="px-1.5 py-1.5">
                        <p class="line-clamp-2 min-h-[1.65rem] text-[11px] font-semibold leading-tight text-slate-800">${p.name}</p>
                        <p class="truncate text-[9px] text-slate-400">${p.code || '—'}</p>
                        ${p.condition ? `<div class="mt-1">${conditionBadgeHtml(p.condition, p.condition_label, true)}</div>` : ''}
                        <p data-product-price="${p.id}" class="mt-0.5 text-xs font-bold leading-tight text-indigo-600">${formatMoney(p.price)} <span class="text-[9px] font-semibold text-slate-500">/${p.unit_label || 'und'}</span></p>
                        <p data-product-cordoba-price="${p.id}" class="text-[10px] font-semibold text-emerald-700">${formatCordobas(p.price)}</p>
                        ${p.discount_pct > 0 ? `<p class="text-[9px] font-bold text-amber-600">${p.discount_label || p.discount_pct + '% OFF'} → ${formatMoney(p.price * (1 - p.discount_pct/100))}</p>` : ''}
                        <p class="mt-0.5 text-[9px] leading-tight ${outStock ? 'text-red-600 font-bold' : lowStock ? 'text-amber-600' : 'text-slate-500'}">
                            ${outStock
                                ? 'Sin stock'
                                : `${formatQty(availableStock)} ${p.base_unit_label || 'und'}${selectedWarehouseId ? ' bodega' : ''}`}
                        </p>
                    </div>
                </button>
                ${(p.sale_units || []).length > 1 ? `<label class="block border-t border-indigo-100 bg-indigo-50 px-1.5 py-1">
                    <select data-product-unit-select="${p.id}" ${outStock ? 'disabled' : ''}
                        onclick="event.stopPropagation()"
                        onchange="updateProductCardUnit(${p.id})"
                        class="w-full rounded border border-indigo-300 bg-white px-1 py-0.5 text-[10px] font-semibold text-slate-800"
                        title="Presentación">
                        ${(p.sale_units || []).map(unit => `<option value="${unit.id}" ${unit.is_default ? 'selected' : ''}>${unit.abbreviation} · ${formatMoney(unit.price)}</option>`).join('')}
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

        if (file.size > 8 * 1024 * 1024) {
            alert('La imagen no puede superar 8 MB.');
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
            showToast(data.message || 'Imagen guardada correctamente.', 'success');
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
        const creditOption = document.getElementById('creditMethodOption');
        if (creditOption) {
            const canCredit = client && client.credit_enabled;
            creditOption.disabled = !canCredit;
            if (canCredit && client.credit_limit > 0) {
                const avail = client.available_credit ?? 0;
                creditOption.title = `Disponible: {{ $currencySymbol }} ${parseFloat(avail).toFixed(2)} · Plazo: ${client.credit_days} días`;
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
                ${c.credit_enabled ? `<p class="text-xs text-slate-500 mt-1">Límite: ${c.credit_limit > 0 ? `{{ $currencySymbol }} ${parseFloat(c.credit_limit).toFixed(2)}` : 'Ilimitado'} · Saldo: {{ $currencySymbol }} ${parseFloat(c.balance || 0).toFixed(2)} · Disponible: ${c.available_credit === null ? 'Ilimitado' : `{{ $currencySymbol }} ${parseFloat(c.available_credit || 0).toFixed(2)}`} · ${c.credit_days}d</p>` : '<p class="text-xs text-slate-400">Solo contado</p>'}
                ${c.price_list_name ? `<p class="text-xs text-indigo-600 mt-1">Lista: ${c.price_list_name}</p>` : ''}
            </button>
        `).join('');
    }

    document.getElementById('clientSearch')?.addEventListener('input', (e) => renderClientsList(e.target.value));

    const paymentMethodIcons = {
        cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        card: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        transfer: 'M8 7h12m0 0l-4-4m4 4l-4 4m0-6H4m6 4v12a3 3 0 003 3h6a3 3 0 003-3V11a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3z',
        credit: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    };

    const paymentMethodNames = { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia', credit: 'Crédito' };

    function updatePaymentMethodSelection() {
        document.querySelectorAll('.pos-pay-option').forEach(btn => {
            btn.classList.toggle('pos-pay-option--active', btn.dataset.method === currentPaymentMethod);
        });
        const summary = document.getElementById('paymentMethodSummary');
        if (summary) summary.textContent = paymentMethodNames[currentPaymentMethod] || currentPaymentMethod;
        const icon = document.getElementById('paymentMethodIcon');
        if (icon) icon.querySelector('path')?.setAttribute('d', paymentMethodIcons[currentPaymentMethod] || paymentMethodIcons.cash);
    }

    function setPaymentPadOpen(open) {
        const pad = document.getElementById('posPaymentPad');
        const toggle = document.getElementById('posPaymentToggle');
        if (!pad) return;
        pad.classList.toggle('pos-pad--open', open);
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    window.togglePaymentPad = function() {
        const pad = document.getElementById('posPaymentPad');
        if (!pad) return;
        setPaymentPadOpen(!pad.classList.contains('pos-pad--open'));
    };

    window.selectPaymentMethod = function(method) {
        const option = document.querySelector(`.pos-pay-option[data-method="${method}"]`);
        if (option?.disabled) return;
        currentPaymentMethod = method;
        updatePaymentMethodSelection();
        updateCreditLimitAlert();
        setPaymentPadOpen(false);
    };

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
        document.getElementById('amountReceived').value = getAmountDue().toFixed(2);
        document.getElementById('amountReceived').dispatchEvent(new Event('input'));
    };

    document.getElementById('amountReceived').addEventListener('input', (e) => {
        const amount = parseFloat(e.target.value) || 0;
        const change = amount - getAmountDue();
        document.getElementById('changeDisplay').textContent = formatMoney(change);
        document.getElementById('amountReceivedInput').value = amount;
    });

    document.getElementById('paymentForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const amountDue = getAmountDue();
        const type = document.getElementById('paymentTypeInput').value;

        if (ticket.length === 0) {
            showToast('Agrega al menos un producto antes de cobrar.', 'warning');
            return;
        }

        if (type === 'cash') {
            const amount = parseFloat(document.getElementById('amountReceived').value) || 0;
            if (amount < amountDue) { alert('Monto recibido insuficiente'); return; }
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
        document.getElementById('tradeInsInput').value = JSON.stringify(tradeIns);
        document.getElementById('warehouseIdInput').value = selectedWarehouseId;
        document.getElementById('notesInput').value = notes;
        document.getElementById('orderDiscountPctInput').value = orderDiscountPct;
        document.getElementById('exchangeRateIdInput').value = posExchangeRate.id || '';
        const submitButton = e.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Procesando venta…';
        e.target.submit();
    });

    window.clearTicket = function() {
        if (ticket.length > 0 && !confirm('¿Descartar ticket actual?')) return;
        ticket = [];
        tradeIns = [];
        orderDiscountPct = 0;
        selectedItemIndex = -1;
        padBuffer = '';
        clearCreditOverride();
        searchInput.value = '';
        renderTicket();
        renderProducts();
    };

    window.createReservationFromPOS = function() {
        if (ticket.length === 0) { showToast('Agrega productos antes de crear el apartado.', 'warning'); return; }
        if (!currentClient) { showToast('Selecciona el cliente que realizará el apartado.', 'warning'); openClientModal(); return; }
        const items = ticket.map(item => {
            const product = products.find(candidate => candidate.id == item.product_id);
            const unit = productUnit(product, item.unit_id);
            return { product_id: item.product_id, quantity: item.quantity * Number(unit?.factor_to_base || 1), price_type: 'retail' };
        });
        sessionStorage.setItem('estelipos.reservationDraft', JSON.stringify({items}));
        const params = new URLSearchParams({client_id: currentClient});
        if (selectedWarehouseId) params.set('warehouse_id', selectedWarehouseId);
        window.location.href = `{{ route('apartados.create') }}?${params.toString()}`;
    };

    function getHeldTickets() {
        return JSON.parse(localStorage.getItem(HELD_KEY) || '[]');
    }

    function updateHeldCount() {
        document.getElementById('heldCount').textContent = getHeldTickets().length;
    }

    // Aviso breve y no bloqueante (reemplaza a los alert() en las facturas en espera).
    function posNotice(message, tone = 'ok') {
        const notice = document.createElement('div');
        notice.setAttribute('role', 'status');
        notice.className = 'fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-lg '
            + (tone === 'warn' ? 'bg-amber-600' : 'bg-emerald-600');
        notice.textContent = message;
        document.body.appendChild(notice);
        setTimeout(() => notice.remove(), 3500);
    }

    function escapeHeldText(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    window.holdTicket = function() {
        if (ticket.length === 0) { posNotice('No hay productos en la factura para dejar en espera.', 'warn'); return; }
        const held = getHeldTickets();
        held.push({
            id: Date.now(),
            label: `Factura en espera ${held.length + 1} · ${document.getElementById('clientDisplay').textContent}`,
            items: ticket,
            tradeIns,
            client: currentClient,
            clientName: document.getElementById('clientDisplay').textContent,
            orderDiscountPct,
            savedAt: new Date().toLocaleString(),
        });
        localStorage.setItem(HELD_KEY, JSON.stringify(held));
        ticket = [];
        tradeIns = [];
        orderDiscountPct = 0;
        currentClient = null;
        document.getElementById('clientDisplay').textContent = 'Cliente General';
        renderTicket();
        updateHeldCount();
        ticketCounter++;
        localStorage.setItem('pos_ticket_counter', ticketCounter);
        document.getElementById('ticketNumber').textContent = ticketCounter;

        posNotice('Factura dejada en espera. Retómala en «En espera» (F6) cuando quieras cobrarla.');
        const heldButton = document.getElementById('heldTicketsBtn');
        heldButton?.classList.add('ring-2', 'ring-white');
        setTimeout(() => heldButton?.classList.remove('ring-2', 'ring-white'), 1800);
    };

    window.showHeldTickets = function() {
        const held = getHeldTickets();
        const list = document.getElementById('heldTicketsList');
        if (held.length === 0) {
            list.innerHTML = '<p class="text-slate-500 text-center py-4 text-sm">No hay facturas en espera.<br><span class="text-xs text-slate-400">Usa «Dejar en espera» (F4) para guardar la factura actual y atender a otro cliente.</span></p>';
        } else {
            list.innerHTML = held.map((h, idx) => `
                <div class="flex items-center justify-between p-3 border border-slate-200 rounded-xl hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 text-sm truncate">${escapeHeldText(h.label)}</p>
                        <p class="text-xs text-slate-600 truncate">${escapeHeldText(h.items.slice(0, 2).map(item => item.name).join(', '))}${h.items.length > 2 ? ` +${h.items.length - 2} más` : ''}</p>
                        <p class="text-xs text-slate-400">${h.items.length} producto(s) · dejada ${escapeHeldText(h.savedAt)}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" onclick="resumeHeldTicket(${idx})" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs font-semibold" title="Volver a esta factura para seguir agregando productos y cobrarla">Retomar</button>
                        <button type="button" onclick="deleteHeldTicket(${idx})" class="px-3 py-1 bg-red-500 text-white rounded-lg text-xs" title="Descartar esta factura en espera">Descartar</button>
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
        if (ticket.length > 0 && !confirm('Ya tienes una factura en curso. Si retomas esta, la factura actual se reemplaza y se pierde. ¿Continuar?\n\nTip: primero usa «Dejar en espera» (F4) para guardarla.')) return;

        ticket = saved.items;
        tradeIns = saved.tradeIns || [];
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
        if (!confirm('¿Descartar esta factura en espera? Se perderán sus productos.')) return;
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

        if (!openModal && quantityEditorOpen && selectedItemIndex >= 0 && !editingText) {
            if (/^[0-9.,]$/.test(e.key)) {
                e.preventDefault();
                if (replaceQuantityOnNextInput) {
                    padBuffer = '';
                    replaceQuantityOnNextInput = false;
                }
                padInput(e.key === ',' ? '.' : e.key);
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                if (padConfirm()) hideQuantityEditor();
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                hideQuantityEditor();
                return;
            }
        }

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
    refreshCatalogPrices();
});
</script>
@endpush

@endsection
