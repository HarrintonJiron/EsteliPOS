@extends('layouts.app')

@section('content')

<div class="space-y-6">
    @include('planilla._nav')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-title">Gestión de Permisos</h1>
            <p class="page-subtitle">Vacaciones y ausencias que afectan días trabajados</p>
        </div>

        <a href="{{ route('leave.create') }}" class="btn-primary">
            + Nueva Solicitud
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex space-x-4">
            <select class="border rounded-lg px-3 py-2">
                <option value="">Todos los estados</option>
                <option value="pending">Pendientes</option>
                <option value="approved">Aprobados</option>
                <option value="rejected">Rechazados</option>
            </select>
            <select class="border rounded-lg px-3 py-2">
                <option value="">Todos los tipos</option>
                <option value="vacation">Vacaciones</option>
                <option value="sick">Enfermedad</option>
                <option value="personal">Personal</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 uppercase text-xs text-gray-600">
<tr>
    <th class="px-6 py-3">Empleado</th>
    <th class="px-6 py-3">Tipo</th>
    <th class="px-6 py-3">Fechas</th>
    <th class="px-6 py-3">Días</th>
    <th class="px-6 py-3">Razón</th>
    <th class="px-6 py-3">Estado</th>
    <th class="px-6 py-3">Acciones</th>
</tr>
</thead>

            <tbody class="divide-y">
                @forelse($leaveRequests as $leave)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ $leave->employee?->name ?? 'Empleado no disponible' }}</td>
                    <td class="px-6 py-4">{{ $leave->type_label }}</td>
                    <td class="px-6 py-4">
                        {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">{{ $leave->days }} días</td>
                    <td class="px-6 py-4">{{ $leave->reason ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs bg-{{ $leave->status_color }}-100 text-{{ $leave->status_color }}-700">
                            {{ $leave->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        @if($leave->status === 'pending')
                            <form action="{{ route('leave.approve', $leave) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('¿Aprobar este permiso?')" class="text-green-600 hover:underline">
                                    Aprobar
                                </button>
                            </form>
                            <button onclick="rejectLeave({{ $leave->id }})" class="text-red-600 hover:underline">
                                Rechazar
                            </button>
                        @endif
                        <a href="{{ route('leave.show', $leave) }}" class="text-blue-600 hover:underline">
                            Ver
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No hay solicitudes de permiso registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

<!-- Modal de Rechazo -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-4 shadow-xl sm:p-6">
        <h3 class="text-lg font-semibold mb-4">Rechazar Permiso</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Razón del rechazo *</label>
                <textarea name="rejection_reason" required rows="3"
                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Rechazar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentLeaveId = null;

function rejectLeave(leaveId) {
    currentLeaveId = leaveId;
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = '{{ route('leave.reject', ':id') }}'.replace(':id', leaveId);
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    currentLeaveId = null;
}
</script>

@endsection
