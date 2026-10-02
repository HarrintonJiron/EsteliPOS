<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\Sale;
use App\Services\CompanySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrintQueueController extends Controller
{
    public function enqueueSale(Request $request, Sale $sale, CompanySettingsService $settings): RedirectResponse
    {
        if ($settings->get()['printing_mode'] !== 'central') {
            return redirect()->route('facturacion.receipt', ['saleId' => $sale->id, 'autoprint' => 1]);
        }

        PrintJob::query()->firstOrCreate(
            ['dedup_key' => 'sale:'.$sale->id],
            [
                'document_type' => 'sale_receipt',
                'document_id' => $sale->id,
                'status' => 'pending',
                'requested_by' => $request->user()?->id,
            ],
        );

        return back()->with('success', 'Ticket enviado a la cola de impresión central.');
    }

    public function station(): View
    {
        return view('printing.station', [
            'pendingCount' => PrintJob::query()->whereIn('status', ['pending', 'processing'])->count(),
        ]);
    }

    public function next(Request $request): JsonResponse
    {
        $job = DB::transaction(function () use ($request): ?PrintJob {
            $job = PrintJob::query()
                ->where(function ($query): void {
                    $query->where('status', 'pending')
                        ->orWhere(function ($stale): void {
                            $stale->where('status', 'processing')
                                ->where('claimed_at', '<', now()->subMinutes(2));
                        });
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->update([
                'status' => 'processing',
                'station_user_id' => $request->user()?->id,
                'claimed_at' => now(),
                'attempts' => $job->attempts + 1,
                'last_error' => null,
            ]);

            return $job;
        });

        if (! $job) {
            return response()->json(['job' => null]);
        }

        $url = match ($job->document_type) {
            'sale_receipt' => route('facturacion.receipt', [
                'saleId' => $job->document_id,
                'autoprint' => 1,
                'central' => 1,
            ]),
            default => null,
        };

        if (! $url) {
            $job->update(['status' => 'failed', 'dedup_key' => null, 'last_error' => 'Tipo de documento no soportado.']);

            return response()->json(['job' => null]);
        }

        return response()->json([
            'job' => [
                'id' => $job->id,
                'label' => 'Ticket de venta #'.$job->document_id,
                'url' => $url,
            ],
        ]);
    }

    public function complete(Request $request, PrintJob $printJob): JsonResponse
    {
        $validated = $request->validate([
            'success' => ['required', 'boolean'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($printJob->status === 'processing', 409);
        abort_unless((int) $printJob->station_user_id === (int) $request->user()?->id, 409);

        if ($validated['success']) {
            $printJob->update([
                'status' => 'printed',
                'printed_at' => now(),
                'dedup_key' => null,
                'last_error' => null,
            ]);
        } else {
            $printJob->update([
                'status' => $printJob->attempts >= 3 ? 'failed' : 'pending',
                'dedup_key' => $printJob->attempts >= 3 ? null : $printJob->dedup_key,
                'last_error' => $validated['error'] ?? 'La estación no pudo imprimir el ticket.',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
