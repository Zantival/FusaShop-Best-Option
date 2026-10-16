<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Models\IntegrationLog;
use App\Services\OrderPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Procesa asíncronamente el evento transaction.updated de Wompi (ya autenticado por el controlador). */
class ProcessWompiEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public ?int $orderId = null;

    public function __construct(public array $transaction)
    {
    }

    protected function service(): string { return 'wompi'; }
    protected function action(): string { return 'transaction.updated'; }

    public function handle(OrderPaymentService $payments): void
    {
        $outcome = $payments->handleTransaction($this->transaction);

        IntegrationLog::success('wompi', 'transaction.updated', null, [
            'transaction_id' => $this->transaction['id'] ?? null,
            'status' => $this->transaction['status'] ?? null,
            'reference' => $this->transaction['reference'] ?? null,
        ], ['outcome' => $outcome]);
    }

    public function failed(\Throwable $e): void
    {
        IntegrationLog::failure(
            'wompi', 'transaction.updated', null, $e,
            ['transaction' => $this->transaction], null,
            $e instanceof \Illuminate\Http\Client\RequestException ? $e->response->status() : null,
            static::class
        );
    }
}
