<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Services\FactusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IssueFactusInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public function __construct(public int $orderId)
    {
    }

    protected function service(): string { return 'factus'; }
    protected function action(): string { return 'bill.validate'; }

    public function handle(FactusService $factus): void
    {
        $order = Order::findOrFail($this->orderId);
        if ($order->payment_status !== 'paid') {
            return;
        }

        // Paso 1: emitir (solo si aún no existe CUFE). Se persiste de inmediato para no duplicar facturas en reintentos.
        if (!$order->invoice_cufe) {
            $r = $factus->issue($order);
            $order->update([
                'invoice_number' => $r['number'],
                'invoice_cufe' => $r['cufe'],
                'invoiced_at' => now(),
            ]);
            IntegrationLog::success('factus', 'bill.validate', $order->id, $r['payload'], $r['raw']);
        }

        // Paso 2: PDF/XML (si fallan, el reintento solo repite la descarga).
        $base = 'invoices/' . $order->reference;
        if (!$order->invoice_pdf_path) {
            Storage::disk('local')->put("{$base}.pdf", $factus->downloadPdf($order->invoice_number));
            $order->update(['invoice_pdf_path' => "{$base}.pdf"]);
        }
        if (!$order->invoice_xml_path) {
            Storage::disk('local')->put("{$base}.xml", $factus->downloadXml($order->invoice_number));
            $order->update(['invoice_xml_path' => "{$base}.xml"]);
        }

        SendOrderApprovedEmail::dispatch($order->id);
    }

    /** Si Factus agota reintentos, el cliente igual recibe su confirmación (sin factura adjunta). */
    public function failed(Throwable $e): void
    {
        $this->traitFailed($e);
        SendOrderApprovedEmail::dispatch($this->orderId);
    }

    private function traitFailed(Throwable $e): void
    {
        $http = $e instanceof \Illuminate\Http\Client\RequestException ? $e->response->status() : null;
        IntegrationLog::failure('factus', 'bill.validate', $this->orderId, $e, [], null, $http, static::class);
    }
}
