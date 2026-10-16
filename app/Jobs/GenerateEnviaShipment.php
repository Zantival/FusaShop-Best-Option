<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Services\EnviaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateEnviaShipment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public function __construct(public int $orderId)
    {
    }

    protected function service(): string { return 'envia'; }
    protected function action(): string { return 'ship.generate'; }

    public function handle(EnviaService $envia): void
    {
        $order = Order::findOrFail($this->orderId);

        if ($order->payment_status !== 'paid' || $order->tracking_number) {
            return; // idempotente: ya tiene guía o no está pagado
        }

        $result = $envia->generate($order);

        // Bloqueo para evitar doble guía si dos workers coinciden.
        DB::transaction(function () use ($order, $result) {
            $fresh = Order::whereKey($order->id)->lockForUpdate()->first();
            if ($fresh->tracking_number) {
                return;
            }
            $fresh->update([
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'],
                'tracking_url' => $result['tracking_url'],
                'shipping_carrier' => $result['carrier'],
                'tracking_status' => 'label_created',
                'packed_at' => $fresh->packed_at ?? now(),
            ]);
        });

        IntegrationLog::success('envia', 'ship.generate', $order->id, $result['payload'], $result['raw']);
    }
}
