@extends('layouts.app')

@section('title', 'Mayor General')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div><h1 class="page-title">Mayor General</h1><p class="page-subtitle">Movimientos y saldos por cuenta</p></div>
        @if($account) @include('contabilidad._export_buttons', ['report' => 'mayor-general']) @endif
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <select name="account_id" class="select-field md:col-span-2" required>
            <option value="">Seleccione una cuenta...</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ (string) request('account_id') === (string) $acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
        <button type="submit" class="btn-primary md:col-span-4 md:w-fit">Consultar</button>
    </form>

    @if($account)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-4 border-l-4 border-indigo-500">
                <p class="text-xs text-slate-500">Cuenta</p>
                <p class="text-lg font-bold text-indigo-600">{{ $account->code }} — {{ $account->name }}</p>
            </div>
            <div class="card p-4 border-l-4 border-slate-400">
                <p class="text-xs text-slate-500">Saldo Inicial</p>
                <p class="text-lg font-bold {{ $openingBalance < 0 ? 'text-red-600' : 'text-slate-800' }}">C$ {{ number_format($openingBalance, 2) }}</p>
            </div>
            <div class="card p-4 border-l-4 border-emerald-500">
                <p class="text-xs text-slate-500">Saldo Final</p>
                <p class="text-lg font-bold {{ $closingBalance < 0 ? 'text-red-600' : 'text-emerald-600' }}">C$ {{ number_format($closingBalance, 2) }}</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <table class="min-w-full table-agro">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Asiento</th>
                        <th>Detalle</th>
                        <th class="text-right">Debe</th>
                        <th class="text-right">Haber</th>
                        <th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-slate-50 font-semibold">
                        <td colspan="5" class="text-right">Saldo Inicial</td>
                        <td class="text-right">C$ {{ number_format($openingBalance, 2) }}</td>
                    </tr>
                    @forelse($movements as $line)
                    <tr>
                        <td>{{ $line->journalEntry->date->format('d/m/Y') }}</td>
                        <td class="font-mono">
                            <a href="{{ route('contabilidad.asientos.show', $line->journalEntry) }}" class="text-indigo-600 hover:underline">{{ $line->journalEntry->number }}</a>
                        </td>
                        <td class="text-sm text-slate-600">{{ $line->detail ?? $line->journalEntry->concept }}</td>
                        <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                        <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                        <td class="text-right font-semibold">C$ {{ number_format($line->running_balance, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500 py-8">Sin movimientos en el período seleccionado.</td>
                    </tr>
                    @endforelse
                    <tr class="bg-slate-50 font-semibold">
                        <td colspan="5" class="text-right">Saldo Final</td>
                        <td class="text-right">C$ {{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="card p-8 text-center text-slate-500">Selecciona una cuenta para ver su mayor.</div>
    @endif

</div>

@endsection
