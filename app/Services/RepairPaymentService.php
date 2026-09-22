<?php

namespace App\Services;

use App\Models\CajaSession;
use App\Models\Client;
use App\Models\NumberSequence;
use App\Models\RepairCreditPayment;
use App\Models\RepairOrder;
use App\Models\Sale;
use App\Models\User;

/**
 * Cobros de reparaciones: registra el pago, lo refleja en caja y deja el
 * estado de pago de la orden al día. Lo comparten la ficha de la reparación
 * (cobrar / abonos) y el módulo de Crédito.
 */
class RepairPaymentService
{
    /**
     * Registra un pago posterior al anticipo (cobro al entregar o abono de crédito).
     * Debe llamarse dentro de una transacción con la orden bloqueada.
     */
    public function pay(
        RepairOrder $order,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $notes = null,
        ?User $user = null,
        ?string $requestToken = null,
    ): RepairCreditPayment {
        $payment = RepairCreditPayment::create([
            'repair_order_id' => $order->id,
            'client_id' => $order->client_id,
            'user_id' => $user?->id,
            'amount' => round($amount, 2),
            'payment_date' => now(),
            'payment_type' => $method,
            'reference_number' => $reference,
            'notes' => $notes,
            'request_token' => $requestToken,
        ]);

        $this->recordCollection($order, $amount, $method, $user);
        $order->syncPaymentStatus();

        return $payment;
    }

    /**
     * Registra en caja el dinero recién cobrado (anticipo, saldo al entregar o
     * abono) para que aparezca al cerrar caja, igual que una venta normal del POS.
     *
     * @throws \RuntimeException si es efectivo y no hay caja abierta
     */
    public function recordCollection(RepairOrder $order, float $amount, string $paymentType, ?User $user = null): void
    {
        if ($amount <= 0.00001) {
            return;
        }

        if ($paymentType === 'credit') {
            // El payment_type del pedido solo dice cómo queda el saldo pendiente, no cómo
            // se cobró este anticipo puntual, así que no podemos asumir efectivo aquí sin
            // arriesgarnos a exigir una caja abierta para un anticipo que en realidad fue
            // tarjeta/transferencia (o bloquear la creación de la reparación por eso).
            // Los abonos reales a un crédito sí declaran su propio método.
            return;
        }

        // sales.payment_type solo admite cash/transfer/credit (igual que Facturación,
        // que guarda "tarjeta" como transfer).
        $paymentType = match ($paymentType) {
            'cash' => 'cash',
            'card', 'transfer', 'check', 'other' => 'transfer',
            default => 'cash',
        };

        $userId = $user?->id ?? $order->user_id;
        $cashSession = $paymentType === 'cash' ? CajaSession::currentForUser($userId) : null;
        if ($paymentType === 'cash' && ! $cashSession) {
            throw new \RuntimeException('Debes abrir una caja antes de cobrar una reparación en efectivo.');
        }

        $clientId = $order->client_id;
        if (! $clientId) {
            $clientId = Client::query()->firstOrCreate(
                // Nombre + teléfono, y nunca un cliente con crédito: dos clientes de mostrador
                // con el mismo nombre no deben compartir historial ni cuenta.
                ['name' => $order->client_name, 'phone' => $order->client_phone, 'credit_enabled' => false],
                ['status' => 'active'],
            )->id;
        }

        Sale::create([
            'invoice_number' => NumberSequence::getNext('factura'),
            'client_id' => $clientId,
            'user_id' => $userId,
            'branch_id' => $cashSession?->branch_id ?? $user?->branch_id,
            'caja_session_id' => $cashSession?->id,
            'repair_order_id' => $order->id,
            'billing_name' => $order->client_name,
            'billing_phone' => $order->client_phone,
            'date' => now(),
            'payment_type' => $paymentType,
            'amount_paid' => $amount,
            'tax_included' => true,
            'tax_rate' => 0,
            'status' => 'completed',
            'notes' => 'Cobro de reparación '.$order->order_number,
            'subtotal' => $amount,
            'tax_total' => 0,
            'total' => $amount,
        ]);
    }
}
