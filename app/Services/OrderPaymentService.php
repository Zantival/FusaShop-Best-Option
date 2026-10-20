<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\GenerateEnviaShipment;
use App\Jobs\IssueFactusInvoice;
use App\Models\IntegrationLog;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados del pago. Toda transición ocurre dentro de una transacción de BD
 * con bloqueo de fila e idempotencia por transaction_id.
 */
class OrderPaymentService
{
    public const DUPLICATE = 'duplicate';
    public const APPROVED = 'approved';
    public const DECLINED = 'declined';
    public const PENDING = 'pending';
    public const REJECTED = 'rejected';

    public function __construct(private StockService $stock)
    {
    }

    /** @param array $tx objeto data.transaction del evento de Wompi */
    public function handleTransaction(array $tx): string
    {
        $transactionId = (string) ($tx['id'] ?? '');
        $status = strtoupper((string) ($tx['status'] ?? ''));
        $reference = (string) ($tx['reference'] ?? '');
        $cents = (int) ($tx['amount_in_cents'] ?? 0);

        if ($transactionId === '' || $reference === '' || $status === '') {
            throw new \InvalidArgumentException('Transacción Wompi incompleta.');
        }

        return DB::transaction(function () use ($tx, $transactionId, $status, $reference, $cents) {
            $record = $this->lockOrCreateRecord($transactionId, $reference, $status, $cents, $tx);

            if ($record->isFinal()) {
                return self::DUPLICATE;
            }

            $order = Order::where('reference', $reference)->lockForUpdate()->first();
            if (!$order) {
                $bannerRequest = \App\Models\BannerRequest::where('payment_proof_path', $reference)
                    ->orWhere('payment_proof_path', 'like', "%{$reference}%")
                    ->lockForUpdate()
                    ->first();

                if ($bannerRequest) {
                    return $this->handleBannerTransaction($bannerRequest, $record, $status, $cents, $tx);
                }

                $record->update(['status' => $status, 'processed_at' => now()]);
                IntegrationLog::failure('wompi', 'transaction.updated', null, "Referencia desconocida {$reference}");
                return self::REJECTED;
            }

            // Un monto distinto al del pedido jamás debe aprobar el pedido.
            if ($cents !== $order->totalInCents() || strtoupper($tx['currency'] ?? $order->currency) !== $order->currency) {
                $record->update(['order_id' => $order->id, 'status' => $status, 'processed_at' => now()]);
                IntegrationLog::failure('wompi', 'amount_mismatch', $order->id, "Monto recibido {$cents} != esperado {$order->totalInCents()}");
                return self::REJECTED;
            }

            $record->update(['order_id' => $order->id, 'status' => $status]);
            $order->wompi_transaction_id = $transactionId;
            $order->wompi_status = $status;

            return match ($status) {
                'APPROVED' => $this->approve($order, $record),
                'DECLINED', 'ERROR', 'VOIDED' => $this->decline($order, $record),
                default => $this->keepPending($order),
            };
        });
    }

    private function lockOrCreateRecord(string $id, string $reference, string $status, int $cents, array $payload): PaymentTransaction
    {
        try {
            PaymentTransaction::firstOrCreate(
                ['transaction_id' => $id],
                ['reference' => $reference, 'status' => $status, 'amount_in_cents' => $cents,
                 'currency' => $payload['currency'] ?? 'COP', 'payload' => $payload]
            );
        } catch (QueryException $e) {
            // Carrera: otra petición insertó el mismo transaction_id; se relee abajo.
        }

        return PaymentTransaction::where('transaction_id', $id)->lockForUpdate()->firstOrFail();
    }

    private function approve(Order $order, PaymentTransaction $record): string
    {
        if ($order->payment_status === 'paid') {
            $record->update(['processed_at' => now()]);
            return self::DUPLICATE;
        }

        // Aprobación tardía sobre un pedido ya cancelado/expirado: intentar re-reservar.
        if ($order->status === 'cancelled') {
            $order->load('items.product');
            try {
                $this->stock->reserve($order);
            } catch (InsufficientStockException $e) {
                $order->save();
                $record->update(['processed_at' => now()]);
                IntegrationLog::failure('wompi', 'late_approval_no_stock', $order->id,
                    'Pago aprobado sobre pedido cancelado sin stock: requiere reembolso manual. ' . $e->getMessage());
                return self::REJECTED;
            }
        }

        $order->load('items.product');
        $this->stock->commit($order);

        $order->forceFill([
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => now(),
            'reservation_expires_at' => null,
        ])->save();

        $record->update(['processed_at' => now()]);
        $this->awardPoints($order);
        $this->notifyMerchants($order);
        $this->clearPurchasedItemsFromCart($order);

        GenerateEnviaShipment::dispatch($order->id)->afterCommit();
        IssueFactusInvoice::dispatch($order->id)->afterCommit();

        return self::APPROVED;
    }

    /** Retira del carrito del cliente únicamente los productos (y opciones) de este pedido pagado. */
    private function clearPurchasedItemsFromCart(Order $order): void
    {
        $cart = \App\Models\Cart::where('user_id', $order->user_id)->first();
        if (!$cart) {
            return;
        }

        $cartItems = $cart->items()->get();
        foreach ($order->items as $orderItem) {
            $match = $cartItems->first(fn ($ci) => (int) $ci->product_id === (int) $orderItem->product_id
                && json_encode($ci->selected_options) === json_encode($orderItem->selected_options));
            if ($match) {
                $match->delete();
                $cartItems = $cartItems->reject(fn ($ci) => $ci->id === $match->id);
            }
        }
    }

    private function decline(Order $order, PaymentTransaction $record): string
    {
        $record->update(['processed_at' => now()]);

        if ($order->payment_status === 'paid') {
            // Ya fue pagado por otra transacción; este rechazo es irrelevante.
            $order->save();
            return self::DUPLICATE;
        }

        $order->load('items.product');
        $this->stock->release($order);
        $this->refundPoints($order);

        $order->forceFill(['payment_status' => 'failed', 'status' => 'cancelled', 'reservation_expires_at' => null])->save();

        $order->user?->notify(new \App\Notifications\PaymentFailedNotification($order));

        return self::DECLINED;
    }

    private function keepPending(Order $order): string
    {
        $order->save();
        return self::PENDING;
    }

    /** Expira pedidos sin pago: libera stock y puntos. Devuelve cantidad expirada. */
    public function expireStaleOrders(): int
    {
        $count = 0;
        Order::where('payment_status', 'pending')->where('status', 'pending')
            ->where('stock_reserved', true)->where('reservation_expires_at', '<', now())
            ->pluck('id')->each(function ($id) use (&$count) {
                DB::transaction(function () use ($id, &$count) {
                    $order = Order::whereKey($id)->lockForUpdate()->first();
                    if (!$order || $order->payment_status !== 'pending') return;
                    $order->load('items.product');
                    $this->stock->release($order);
                    $this->refundPoints($order);
                    $order->forceFill(['status' => 'cancelled', 'payment_status' => 'failed'])->save();
                    $count++;
                });
            });

        return $count;
    }

    private function merchantSubtotals(Order $order): array
    {
        $out = [];
        foreach ($order->items as $item) {
            $mid = $item->product->merchant_id;
            $out[$mid] = ($out[$mid] ?? 0) + $item->quantity * (float) $item->price;
        }
        return $out;
    }

    private function refundPoints(Order $order): void
    {
        $used = (int) $order->points_used;
        if ($used <= 0) return;

        $subs = $this->merchantSubtotals($order);
        $total = array_sum($subs) ?: 1;
        $left = $used;
        $last = array_key_last($subs);
        foreach ($subs as $mid => $sub) {
            $share = $mid === $last ? $left : (int) floor($used * $sub / $total);
            $left -= $share;
            if ($share > 0) {
                $rec = LoyaltyPoint::firstOrCreate(['user_id' => $order->user_id, 'merchant_id' => $mid], ['points' => 0]);
                $rec->increment('points', $share);
            }
        }
    }

    private function awardPoints(Order $order): void
    {
        $earned = (int) $order->points_earned;
        $used = (int) $order->points_used;

        if ($earned > 0 && $order->user_id) {
            $subs = $this->merchantSubtotals($order);
            $total = array_sum($subs) ?: 1;
            $left = $earned;
            $last = array_key_last($subs);
            foreach ($subs as $mid => $sub) {
                $share = $mid === $last ? $left : (int) floor($earned * $sub / $total);
                $left -= $share;
                if ($share > 0) {
                    $rec = LoyaltyPoint::firstOrCreate(
                        ['user_id' => $order->user_id, 'merchant_id' => $mid],
                        ['points' => 0]
                    );
                    $rec->increment('points', $share);
                }
            }
        }

        if (($earned > 0 || $used > 0) && $order->user) {
            $order->user->notify(new \App\Notifications\LoyaltyPointsNotification(
                $order,
                $earned,
                $used,
                (float) $order->discount
            ));
        }
    }

    private function handleBannerTransaction(\App\Models\BannerRequest $bannerRequest, PaymentTransaction $record, string $status, int $cents, array $tx): string
    {
        $expectedCents = (int) round(((float) $bannerRequest->cost) * 100);
        if ($cents !== $expectedCents || strtoupper($tx['currency'] ?? 'COP') !== 'COP') {
            $record->update(['status' => $status, 'processed_at' => now()]);
            IntegrationLog::failure('wompi', 'banner.amount_mismatch', null, "Monto recibido {$cents} != esperado {$expectedCents}", ['banner_request_id' => $bannerRequest->id]);
            return self::REJECTED;
        }

        $record->update(['status' => $status, 'processed_at' => now()]);

        if ($status === 'APPROVED') {
            IntegrationLog::success('wompi', 'banner.payment_approved', null, [
                'banner_request_id' => $bannerRequest->id,
                'transaction_id'    => $record->transaction_id,
                'reference'         => $record->reference,
                'amount_cents'      => $cents,
            ]);
            return self::APPROVED;
        }

        if (in_array($status, ['DECLINED', 'ERROR', 'VOIDED'], true)) {
            $bannerRequest->update([
                'status'           => 'rejected',
                'rejection_reason' => 'Pago rechazado en pasarela Wompi (' . $status . ').',
            ]);
            return self::DECLINED;
        }

        return self::PENDING;
    }

    private function notifyMerchants(Order $order): void
    {
        foreach (array_keys($this->merchantSubtotals($order)) as $mid) {
            User::find($mid)?->notify(new NewOrderNotification($order));
        }
    }
}
