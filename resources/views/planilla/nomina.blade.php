@extends('layouts.app')

@section('content')

<div class="p-6 space-y-6">

    <h1 class="text-2xl font-bold text-gray-800">
        Nómina Mensual
    </h1>

    <div class="flex justify-between items-center">

    <h1 class="text-2xl font-bold text-gray-800">
        Nómina Mensual - Febrero 2026
    </h1>

    <div class="space-x-3">
        <span id="estadoNomina"
            class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded-full text-sm">
            Abierta
        </span>

        <button onclick="cerrarNomina()"
            class="bg-red-600 text-white px-4 py-2 rounded-lg">
            Cerrar Nómina
        </button>
    </div>

</div>


    <!-- Resumen -->
    <div class="grid grid-cols-4 gap-6">

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-sm text-gray-500">Período</p>
            <p class="text-xl font-bold">Febrero 2026</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-sm text-gray-500">Total Bruto</p>
            <p class="text-xl font-bold text-green-700">C$ 85,000</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-red-600">
            <p class="text-sm text-gray-500">Total Deducciones</p>
            <p class="text-xl font-bold text-red-700">C$ 12,000</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow border-l-4 border-purple-600">
            <p class="text-sm text-gray-500">Total Neto</p>
            <p class="text-xl font-bold text-purple-700">C$ 73,000</p>
        </div>

    </div>
    <div class="bg-white p-6 rounded-xl shadow">
    <h2 class="text-lg font-semibold mb-4">
        Costo de Nómina Últimos 6 Meses
    </h2>

    <canvas id="graficoNomina" height="100"></canvas>
</div>


    <!-- Tabla Nómina -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 uppercase text-xs text-gray-600">
                <tr>
                    <th class="px-6 py-3">Empleado</th>
                    <th class="px-6 py-3">Salario</th>
                    <th class="px-6 py-3">Bonos</th>
                    <th class="px-6 py-3">INSS</th>
                    <th class="px-6 py-3">IR</th>
                    <th class="px-6 py-3">Neto</th>
                    <th class="px-6 py-3">Recibo</th>
                </tr>
            </thead>

            <tbody class="divide-y">

                <tr>
                    <td class="px-6 py-4">Luis García</td>
                    <td class="px-6 py-4">C$ 12,000</td>
                    <td class="px-6 py-4">C$ 1,000</td>
                    <td class="px-6 py-4 text-red-600">C$ 750</td>
                    <td class="px-6 py-4 text-red-600">C$ 300</td>
                    <td class="px-6 py-4 font-semibold">C$ 11,950</td>
                    <td class="px-6 py-4">
    <button onclick="abrirRecibo('Luis García')" 
        class="text-indigo-600 hover:underline">
        Ver Recibo
    </button>

    <button onclick="verHistorial()"
    class="bg-gray-700 text-white px-4 py-2 rounded-lg">
    Historial de Recibos
</button>

</td>

                </tr>

                 <tr>
                    <td class="px-6 py-4">Luis García</td>
                    <td class="px-6 py-4">C$ 12,000</td>
                    <td class="px-6 py-4">C$ 1,000</td>
                    <td class="px-6 py-4 text-red-600">C$ 750</td>
                    <td class="px-6 py-4 text-red-600">C$ 300</td>
                    <td class="px-6 py-4 font-semibold">C$ 11,950</td>
                    <td class="px-6 py-4">
    <button onclick="abrirRecibo('Luis García')" 
        class="text-indigo-600 hover:underline">
        Ver Recibo
    </button>
    <button onclick="verHistorial()"
    class="bg-gray-700 text-white px-4 py-2 rounded-lg">
    Historial de Recibos
</button>

</td>

                </tr>

                 <tr>
                    <td class="px-6 py-4">Luis García</td>
                    <td class="px-6 py-4">C$ 12,000</td>
                    <td class="px-6 py-4">C$ 1,000</td>
                    <td class="px-6 py-4 text-red-600">C$ 750</td>
                    <td class="px-6 py-4 text-red-600">C$ 300</td>
                    <td class="px-6 py-4 font-semibold">C$ 11,950</td>
                    <td class="px-6 py-4">
    <button onclick="abrirRecibo('Luis García')" 
        class="text-indigo-600 hover:underline">
        Ver Recibo
    </button>

    <button onclick="verHistorial()"
    class="bg-gray-700 text-white px-4 py-2 rounded-lg">
    Historial de Recibos
</button>

</td>

                </tr>

                 <tr>
                    <td class="px-6 py-4">Luis García</td>
                    <td class="px-6 py-4">C$ 12,000</td>
                    <td class="px-6 py-4">C$ 1,000</td>
                    <td class="px-6 py-4 text-red-600">C$ 750</td>
                    <td class="px-6 py-4 text-red-600">C$ 300</td>
                    <td class="px-6 py-4 font-semibold">C$ 11,950</td>
                    <td class="px-6 py-4">
    <button onclick="abrirRecibo('Luis García')" 
        class="text-indigo-600 hover:underline">
        Ver Recibo
    </button>

    <button onclick="verHistorial()"
    class="bg-gray-700 text-white px-4 py-2 rounded-lg">
    Historial de Recibos
</button>

</td>

                </tr>

            </tbody>

        </table>

        <!-- Modal Recibo -->
<div id="modalRecibo"
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">

    <div class="bg-white w-[800px] rounded-xl shadow-2xl p-8 space-y-6">

        <div class="flex justify-between items-center border-b pb-4">
            <div>
                <h2 class="text-xl font-bold">AgroCampo</h2>
                <p class="text-sm text-gray-500">
                    Recibo de Pago de Nómina
                </p>
            </div>

            <p class="text-sm text-gray-600">
                Período: Febrero 2026
            </p>
        </div>

        <!-- Datos empleado -->
        <div class="grid grid-cols-2 gap-6 text-sm">

            <div>
                <p><strong>Empleado:</strong> <span id="nombreEmpleado"></span></p>
                <p><strong>Cargo:</strong> Vendedor</p>
                <p><strong>Fecha Ingreso:</strong> 15/02/2022</p>
            </div>

            <div>
                <p><strong>Cédula:</strong> 001-120398-1001A</p>
                <p><strong>Contrato:</strong> Tiempo Completo</p>
                <p><strong>Días Trabajados:</strong> 30</p>
            </div>

        </div>

        <!-- Detalle -->
        <div class="border-t pt-4">

            <table class="w-full text-sm">

                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Concepto</th>
                        <th class="px-4 py-2 text-right">Monto</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                    <tr>
                        <td class="px-4 py-2">Salario Base</td>
                        <td class="px-4 py-2 text-right">C$ 12,000</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2">Bonos</td>
                        <td class="px-4 py-2 text-right text-green-600">C$ 1,000</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2">INSS</td>
                        <td class="px-4 py-2 text-right text-red-600">- C$ 750</td>
                    </tr>

                    <tr>
                        <td class="px-4 py-2">IR</td>
                        <td class="px-4 py-2 text-right text-red-600">- C$ 300</td>
                    </tr>

                </tbody>

                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td class="px-4 py-3 text-right">Total Neto</td>
                        <td class="px-4 py-3 text-right text-lg">
                            C$ 11,950
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

        <!-- Firmas -->
        <div class="grid grid-cols-2 gap-10 pt-10 text-center text-sm">

            <div>
                <div class="border-t pt-2">Firma Empleado</div>
            </div>

            <div>
                <div class="border-t pt-2">Firma Empresa</div>
            </div>

        </div>

        <div class="flex justify-end pt-6 space-x-4">

            <button onclick="imprimirRecibo()"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg">
                Imprimir
            </button>

            <button onclick="cerrarRecibo()"
                class="bg-gray-300 px-4 py-2 rounded-lg">
                Cerrar
            </button>

            <button onclick="verHistorial()"
    class="bg-gray-700 text-white px-4 py-2 rounded-lg">
    Historial de Recibos
</button>

<button onclick="exportarPDF()"
    class="bg-purple-600 text-white px-4 py-2 rounded-lg">
    Exportar PDF
</button>



        </div>

    </div>
</div>


    </div>

</div>
<div id="modalHistorial"
     class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">

    <div class="bg-white w-[700px] p-6 rounded-xl shadow-xl space-y-6">

        <h2 class="text-lg font-semibold">
            Historial de Pagos - Luis García
        </h2>

        <table class="w-full text-sm">

            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Período</th>
                    <th class="px-4 py-2">Neto Pagado</th>
                    <th class="px-4 py-2">Estado</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                <tr>
                    <td class="px-4 py-2">Enero 2026</td>
                    <td class="px-4 py-2">C$ 11,800</td>
                    <td class="px-4 py-2 text-green-600">Pagado</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Diciembre 2025</td>
                    <td class="px-4 py-2">C$ 11,750</td>
                    <td class="px-4 py-2 text-green-600">Pagado</td>
                </tr>
            </tbody>

        </table>

        <div class="text-right">
            <button onclick="cerrarHistorial()"
                class="bg-gray-300 px-4 py-2 rounded-lg">
                Cerrar
            </button>
        </div>

    </div>
</div>


<script>

    function verHistorial() {
    document.getElementById('modalHistorial').classList.remove('hidden');
}

function cerrarHistorial() {
    document.getElementById('modalHistorial').classList.add('hidden');
}
function exportarPDF() {
    alert("Recibo exportado en formato PDF");
}



function abrirRecibo(nombre) {
    document.getElementById('nombreEmpleado').innerText = nombre;
    document.getElementById('modalRecibo').classList.remove('hidden');
}

function cerrarRecibo() {
    document.getElementById('modalRecibo').classList.add('hidden');
}

function imprimirRecibo() {
    window.print();
}



</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('graficoNomina');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Sep', 'Oct', 'Nov', 'Dic', 'Ene', 'Feb'],
        datasets: [{
            label: 'Costo Mensual',
            data: [68000, 70000, 72000, 69000, 71000, 73000],
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.2)',
            tension: 0.3
        }]
    }
});
</script>


<script>
function cerrarNomina() {
    document.getElementById('estadoNomina').innerText = "Cerrada";
    document.getElementById('estadoNomina').classList =
        "bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm";
    alert("Nómina cerrada correctamente");
}
</script>



@endsection
