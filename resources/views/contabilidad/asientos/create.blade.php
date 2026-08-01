@extends('layouts.app')

@section('title', 'Nuevo Asiento Contable')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    @include('contabilidad._tabs')

    <div>
        <h1 class="page-title">Nuevo Asiento Contable</h1>
        <p class="page-subtitle">El asiento se guarda en borrador; debes contabilizarlo después para que impacte los reportes</p>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('contabilidad.asientos.store') }}" class="card p-6 space-y-6" id="entryForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required class="input-field">
                @error('date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Concepto</label>
                <input type="text" name="concept" value="{{ old('concept') }}" required class="input-field">
                @error('concept')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Referencia</label>
                <input type="text" name="reference" value="{{ old('reference') }}" placeholder="Ej. Factura FAC-000123" class="input-field">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="input-field">
            </div>
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <h2 class="font-semibold text-slate-800">Líneas del asiento</h2>
                <button type="button" id="addLine" class="btn-outline text-sm">+ Agregar línea</button>
            </div>

            @error('lines')<p class="text-red-600 text-xs mb-2">{{ $message }}</p>@enderror

            <table class="min-w-full table-agro" id="linesTable">
                <thead>
                    <tr>
                        <th>Cuenta</th>
                        <th>Centro de Costo</th>
                        <th>Detalle</th>
                        <th class="text-right">Debe</th>
                        <th class="text-right">Haber</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="linesBody"></tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td colspan="3" class="text-right">Totales</td>
                        <td class="text-right" id="totalDebit">0.00</td>
                        <td class="text-right" id="totalCredit">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <p id="balanceWarning" class="text-red-600 text-xs mt-2 hidden">El asiento no está balanceado: Debe debe ser igual a Haber.</p>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('contabilidad.asientos.index') }}" class="btn-outline">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar Asiento</button>
        </div>
    </form>
</div>

<template id="lineTemplate">
    <tr>
        <td>
            <select name="lines[__INDEX__][account_id]" class="select-field" required>
                <option value="">Seleccione cuenta...</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="lines[__INDEX__][cost_center_id]" class="select-field">
                <option value="">—</option>
                @foreach($costCenters as $costCenter)
                    <option value="{{ $costCenter->id }}">{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="lines[__INDEX__][detail]" class="input-field"></td>
        <td><input type="number" step="0.01" min="0" name="lines[__INDEX__][debit]" value="0" class="input-field text-right line-debit"></td>
        <td><input type="number" step="0.01" min="0" name="lines[__INDEX__][credit]" value="0" class="input-field text-right line-credit"></td>
        <td class="text-center"><button type="button" class="text-red-600 removeLine">✕</button></td>
    </tr>
</template>

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('linesBody');
        const template = document.getElementById('lineTemplate');
        let index = 0;

        function addLine() {
            const html = template.innerHTML.replaceAll('__INDEX__', index++);
            const tr = document.createElement('tbody');
            tr.innerHTML = html;
            body.appendChild(tr.firstElementChild);
            recalculate();
        }

        function recalculate() {
            let totalDebit = 0, totalCredit = 0;
            body.querySelectorAll('tr').forEach(row => {
                totalDebit += parseFloat(row.querySelector('.line-debit')?.value || 0);
                totalCredit += parseFloat(row.querySelector('.line-credit')?.value || 0);
            });
            document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
            document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);
            document.getElementById('balanceWarning').classList.toggle('hidden', Math.abs(totalDebit - totalCredit) < 0.01);
        }

        document.getElementById('addLine').addEventListener('click', addLine);
        body.addEventListener('input', recalculate);
        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('removeLine')) {
                e.target.closest('tr').remove();
                recalculate();
            }
        });

        addLine();
        addLine();
    })();
</script>
@endpush

@endsection
