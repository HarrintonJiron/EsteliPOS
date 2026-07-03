@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Gestión de Planilla
        </h1>

        <a href="{{ route('nomina.index') }}"
   class="bg-indigo-600 text-white px-4 py-2 rounded-lg">
   Ver Nómina
</a>


        <button onclick="document.getElementById('modalEmpleado').classList.remove('hidden')"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg">
            + Nuevo Empleado
        </button>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-3 gap-6">

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Total Empleados</p>
            <p class="text-2xl font-bold text-blue-700">8</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Planilla Mensual</p>
            <p class="text-2xl font-bold text-green-700">C$ 85,000</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Activos</p>
            <p class="text-2xl font-bold text-purple-700">7</p>
        </div>

    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 uppercase text-xs text-gray-600">
<tr>
    <th class="px-6 py-3">Nombre</th>
    <th class="px-6 py-3">Cédula</th>
    <th class="px-6 py-3">Cargo</th>
    <th class="px-6 py-3">Fecha Ingreso</th>
    <th class="px-6 py-3">Tipo Contrato</th>
    <th class="px-6 py-3">Salario Base</th>
    <th class="px-6 py-3">Estado</th>
    <th class="px-6 py-3">Acciones</th>
</tr>
</thead>



            <tbody class="divide-y">

<tr>
    <td class="px-6 py-4 font-medium">Luis García</td>
    <td class="px-6 py-4">Vendedor</td>
    <td class="px-6 py-4">C$ 12,000</td>
    <td class="px-6 py-4 text-green-600">C$ 1,000</td>
    <td class="px-6 py-4 text-red-600">C$ 500</td>
    <td class="px-6 py-4 font-semibold">C$ 12,500</td>
    <td class="px-6 py-4">
        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
            Pendiente
        </span>
    </td>
    <td class="px-6 py-4 space-x-3">
        <button onclick="pagarEmpleado()" class="text-green-600 hover:underline">
            Pagar
        </button>
        <button onclick="editarEmpleado()" class="text-indigo-600 hover:underline">
            Editar
        </button>
        <button onclick="eliminarEmpleado()" class="text-red-600 hover:underline">
            Eliminar
        </button>
    </td>
</tr>

<tr>
    <td class="px-6 py-4 font-medium">jorge polanco</td>
    <td class="px-6 py-4">Carguero</td>
    <td class="px-6 py-4">C$ 9,000</td>
    <td class="px-6 py-4 text-green-600">C$ 1,000</td>
    <td class="px-6 py-4 text-red-600">C$ 450</td>
    <td class="px-6 py-4 font-semibold">C$ 9,550</td>
    <td class="px-6 py-4">
        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
            Pendiente
        </span>
    </td>
    <td class="px-6 py-4 space-x-3">
        <button onclick="pagarEmpleado()" class="text-green-600 hover:underline">
            Pagar
        </button>
        <button onclick="editarEmpleado()" class="text-indigo-600 hover:underline">
            Editar
        </button>
        <button onclick="eliminarEmpleado()" class="text-red-600 hover:underline">
            Eliminar
        </button>
    </td>
</tr>

<tr>
    <td class="px-6 py-4 font-medium">Carlos sevilla</td>
    <td class="px-6 py-4">Vendedor</td>
    <td class="px-6 py-4">C$ 12,000</td>
    <td class="px-6 py-4 text-green-600">C$ 1,000</td>
    <td class="px-6 py-4 text-red-600">C$ 500</td>
    <td class="px-6 py-4 font-semibold">C$ 12,500</td>
    <td class="px-6 py-4">
        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
            Pendiente
        </span>
    </td>
    <td class="px-6 py-4 space-x-3">
        <button onclick="pagarEmpleado()" class="text-green-600 hover:underline">
            Pagar
        </button>
        <button onclick="editarEmpleado()" class="text-indigo-600 hover:underline">
            Editar
        </button>
        <button onclick="eliminarEmpleado()" class="text-red-600 hover:underline">
            Eliminar
        </button>
    </td>
</tr>


</tbody>


        </table>

    </div>

</div>
<!-- Modal Empleado -->
<div id="modalEmpleado"
     class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">

    <div class="bg-white rounded-xl shadow-xl w-[600px] p-6 space-y-6">

        <h2 class="text-lg font-semibold">Registrar Empleado</h2>

        <div class="grid grid-cols-2 gap-4">

            <input type="text" placeholder="Nombre"
                class="border rounded-lg px-3 py-2 w-full">

            <input type="text" placeholder="Cargo"
                class="border rounded-lg px-3 py-2 w-full">

            <input type="number" placeholder="Salario Base"
                class="border rounded-lg px-3 py-2 w-full">

            <input type="number" placeholder="Bonos"
                class="border rounded-lg px-3 py-2 w-full">

            <input type="number" placeholder="Deducciones"
                class="border rounded-lg px-3 py-2 w-full">

        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t">
            <button onclick="cerrarEmpleado()"
                class="px-4 py-2 bg-gray-300 rounded-lg">
                Cancelar
            </button>

            <button onclick="guardarEmpleado()"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                Guardar
            </button>
        </div>

    </div>
</div>

<script>

function cerrarEmpleado() {
    document.getElementById('modalEmpleado').classList.add('hidden');
}

function guardarEmpleado() {
    alert('Empleado registrado correctamente');
    cerrarEmpleado();
}

function pagarEmpleado() {
    alert('Empleado marcado como PAGADO');
}

function editarEmpleado() {
    alert('Editar empleado (vista demostrativa)');
}

function eliminarEmpleado() {
    if(confirm('¿Desea eliminar este empleado?')) {
        alert('Empleado eliminado');
    }
}

</script>

@endsection
