@extends('layouts.app')

@section('title', 'Asientos Contables')

@section('content')

<div class="space-y-6">

    @include('contabilidad._tabs')

    <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
            <h1 class="page-title">Asientos Contables</h1>
            <p class="page-subtitle">Pólizas contables bajo partida doble</p>
        </div>
        @can('create', \App\Models\JournalEntry::class)
            <a href="{{ route('contabilidad.asientos.create') }}" class="btn-primary">+ Nuevo Asiento</a>
        @endcan
    </div>

    <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Número, concepto o referencia..." class="input-field md:col-span-2">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
        <select name="status" class="select-field">
            <option value="">Todos los estados</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
            <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Contabilizado</option>
            <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Anulado</option>
        </select>
        <button type="submit" class="btn-primary md:col-span-5 md:w-fit">Filtrar</button>
    </form>

    <div class="card overflow-hidden">
        <table class="min-w-full table-agro">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th class="text-right">Debe</th>
                    <th class="text-right">Haber</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td class="font-mono">{{ $entry->number }}</td>
                    <td>{{ $entry->date->format('d/m/Y') }}</td>
                    <td class="text-slate-700">{{ $entry->concept }}</td>
                    <td class="text-right">C$ {{ number_format($entry->total_debit, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($entry->total_credit, 2) }}</td>
                    <td class="text-sm text-slate-500">{{ $entry->user?->name }}</td>
                    <td>
                        @if($entry->status === 'draft' && auth()->user()->can('post', $entry))
                            <span class="badge-warning">Borrador</span>
                        @elseif($entry->status === 'posted')
                            <span class="badge-success">Contabilizado</span>
                        @else
                            <span class="badge-danger">Anulado</span>
                        @endif
                    </td>
                    <td class="text-center whitespace-nowrap space-x-2">
                        <a href="{{ route('contabilidad.asientos.show', $entry) }}" class="text-indigo-600 hover:underline text-sm">Ver</a>
                        @if($entry->status === 'draft')
                            <form action="{{ route('contabilidad.asientos.post', $entry) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-emerald-600 hover:underline text-sm">Contabilizar</button>
                            </form>
                            @can('delete', $entry)<form action="{{ route('contabilidad.asientos.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este asiento en borrador?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Eliminar</button>
                            </form>@endcan
                        @elseif($entry->status === 'posted' && auth()->user()->can('void', $entry))
                            <form action="{{ route('contabilidad.asientos.void', $entry) }}" method="POST" class="inline" onsubmit="return confirm('¿Anular este asiento contabilizado?')">
                                @csrf
                                <button type="submit" class="text-red-600 hover:underline text-sm">Anular</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-500 py-8">No hay asientos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $entries->links() }}</div>

</div>

@endsection
