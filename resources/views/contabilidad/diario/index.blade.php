@extends('layouts.app')

@section('title', 'Diario General')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Diario General</h1>
            <p class="page-subtitle">Movimientos contabilizados en orden cronológico</p>
        </div>
        @include('contabilidad._export_buttons', ['report' => 'diario-general'])
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
        <select name="account_id" class="select-field">
            <option value="">Todas las cuentas</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" {{ (string) request('account_id') === (string) $account->id ? 'selected' : '' }}>{{ $account->code }} - {{ $account->name }}</option>
            @endforeach
        </select>
        <select name="user_id" class="select-field">
            <option value="">Todos los usuarios</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="movement_type" class="select-field">
            <option value="">Debe y Haber</option>
            <option value="debit" {{ request('movement_type') === 'debit' ? 'selected' : '' }}>Solo Debe</option>
            <option value="credit" {{ request('movement_type') === 'credit' ? 'selected' : '' }}>Solo Haber</option>
        </select>
        <button type="submit" class="btn-primary md:col-span-5 md:w-fit">Filtrar</button>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Asiento</th>
                    <th>Concepto</th>
                    <th>Cuenta</th>
                    <th>Detalle</th>
                    <th class="text-right">Debe</th>
                    <th class="text-right">Haber</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    @foreach($entry->lines as $line)
                    <tr>
                        <td>{{ $entry->date->format('d/m/Y') }}</td>
                        <td class="font-mono">
                            <a href="{{ route('contabilidad.asientos.show', $entry) }}" class="text-indigo-600 hover:underline">{{ $entry->number }}</a>
                        </td>
                        <td class="text-sm text-slate-600">{{ $entry->concept }}</td>
                        <td class="text-sm">{{ $line->account->code }} - {{ $line->account->name }}</td>
                        <td class="text-sm text-slate-500">{{ $line->detail ?? '—' }}</td>
                        <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                        <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                        <td class="text-sm text-slate-500">{{ $entry->user?->name }}</td>
                    </tr>
                    @endforeach
                @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-500 py-8">No hay movimientos contabilizados con estos filtros.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
