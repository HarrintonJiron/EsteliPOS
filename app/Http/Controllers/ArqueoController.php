<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\CreditPayment;
use App\Models\Arqueo;
use Carbon\Carbon;

class ArqueoController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // Buscar sesión de caja abierta para hoy
        $today = Carbon::today();
        $openSession = \App\Models\CajaSession::whereDate('date', $today->toDateString())->where('status','open')->first();

        return view('arqueo.wait', ['now' => $now, 'openSession' => $openSession]);
    }

    public function open(Request $request)
    {
        $date = Carbon::today();
        // Si ya existe una sesión abierta para hoy, regresar
        $existing = \App\Models\CajaSession::whereDate('date', $date->toDateString())->where('status','open')->first();
        if ($existing) {
            return redirect()->route('arqueo.index');
        }

        $session = \App\Models\CajaSession::create([
            'date' => $date->toDateString(),
            'opened_at' => Carbon::now(),
            'opened_by' => auth()->id() ?? null,
            'status' => 'open',
        ]);

        return redirect()->route('arqueo.index');
    }

    public function run(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $sales = Sale::whereDate('date', $date->toDateString())->with('client', 'details')->get();

        $totalSalesCount = $sales->count();
        $totalSalesAmount = $sales->sum('total');

        $byType = $sales->groupBy('payment_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

        $creditPayments = CreditPayment::whereDate('payment_date', $date->toDateString())->with('client')->get();
        $creditPaymentsTotal = $creditPayments->sum('amount');

        // Procesar conteo físico enviado desde el formulario
        $physicalCounts = $request->input('physical_counts', []);
        $physicalTotal = 0;
        if (is_array($physicalCounts) && count($physicalCounts)) {
            foreach ($physicalCounts as $c) {
                $amount = floatval(str_replace(',', '', ($c['amount'] ?? 0)));
                $qty = floatval(str_replace(',', '', ($c['qty'] ?? 0)));
                $physicalTotal += ($amount * $qty);
            }
        }

        // Guardar el arqueo en la base de datos para historial
        $cashTotal = floatval($byType['cash']['total'] ?? 0);
        $difference = floatval($physicalTotal) - $cashTotal;

        $arqueo = Arqueo::create([
            'date' => $date->toDateString(),
            'user_id' => auth()->id() ?? null,
            'caja_session_id' => $request->input('caja_session_id') ?? null,
            'total_sales_count' => $totalSalesCount,
            'total_sales_amount' => $totalSalesAmount,
            'cash_total' => $cashTotal,
            'credit_payments_total' => $creditPaymentsTotal,
            'physical_total' => $physicalTotal,
            'difference' => $difference,
            'details' => [
                'sales' => $sales->pluck('id')->toArray(),
                'credit_payments' => $creditPayments->pluck('id')->toArray(),
                'physical_counts' => $physicalCounts,
            ],
        ]);

        // Cerrar sesión de caja si se pasó caja_session_id
        if ($request->input('caja_session_id')) {
            $cs = \App\Models\CajaSession::find($request->input('caja_session_id'));
            if ($cs) {
                $cs->closed_at = Carbon::now();
                $cs->closed_by = auth()->id() ?? null;
                $cs->status = 'closed';
                $cs->save();
            }
        }

        return view('arqueo.report', [
            'date' => $date,
            'sales' => $sales,
            'totalSalesCount' => $totalSalesCount,
            'totalSalesAmount' => $totalSalesAmount,
            'byType' => $byType,
            'creditPayments' => $creditPayments,
            'creditPaymentsTotal' => $creditPaymentsTotal,
            'physicalTotal' => $physicalTotal,
            'physicalCounts' => $physicalCounts,
            'arqueo' => $arqueo,
        ]);
    }
}
