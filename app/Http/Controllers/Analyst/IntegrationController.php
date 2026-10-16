<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateEnviaShipment;
use App\Jobs\IssueFactusInvoice;
use App\Jobs\ProcessWompiEvent;
use App\Jobs\SendOrderApprovedEmail;
use App\Jobs\SendOrderShippedEmail;
use App\Models\AdminAuditLog;
use App\Models\IntegrationLog;
use App\Models\Order;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public const MAX_RETRIES = 5;

    public function index(Request $request)
    {
        $logs = IntegrationLog::with('order:id,reference,invoice_cufe,tracking_number')
            ->when($request->filled('service'), fn ($q) => $q->where('service', $request->service))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(30)->withQueryString();

        $failed24h = IntegrationLog::where('status', 'failed')->whereNull('resolved_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('service, count(*) c')
            ->groupBy('service')
            ->pluck('c', 'service');

        return view('analyst.integrations', compact('logs', 'failed24h'));
    }

    public function retry(IntegrationLog $log)
    {
        abort_unless(in_array($log->status, ['failed', 'manual_intervention'], true) && $log->resolved_at === null, 422, 'Este registro no admite reintento.');

        if ($log->retries >= self::MAX_RETRIES) {
            $log->update(['status' => 'manual_intervention']);
            return back()->with('error', 'Límite máximo de reintentos (' . self::MAX_RETRIES . ') alcanzado. Requiere intervención manual.');
        }

        // Verificación de idempotencia antes de re-despachar (§2.5)
        $order = $log->order_id ? Order::find($log->order_id) : null;
        if ($log->retry_job === IssueFactusInvoice::class && $order && !empty($order->invoice_cufe)) {
            $log->update(['resolved_at' => now()]);
            return back()->with('success', "Idempotencia: El pedido #{$order->id} ya cuenta con CUFE DIAN ({$order->invoice_cufe}). Registro marcado como resuelto sin duplicar factura.");
        }
        if ($log->retry_job === GenerateEnviaShipment::class && $order && !empty($order->tracking_number)) {
            $log->update(['resolved_at' => now()]);
            return back()->with('success', "Idempotencia: El pedido #{$order->id} ya tiene guía Envia ({$order->tracking_number}). Registro marcado como resuelto sin duplicar despacho.");
        }

        $dispatched = $this->dispatchRetryJob($log);
        if (!$dispatched) {
            return back()->with('error', 'Este fallo no tiene una acción de reintento asociada.');
        }

        $newRetries = $log->retries + 1;
        $log->update([
            'resolved_at' => now(),
            'retries'     => $newRetries,
            'status'      => $newRetries >= self::MAX_RETRIES ? 'manual_intervention' : $log->status,
        ]);

        AdminAuditLog::record('integration.retried', $log, "Reintento manual #{$newRetries} para servicio {$log->service}");

        return back()->with('success', 'Reintento idempotente encolado. Revisa el resultado en unos segundos.');
    }

    public function retryBulk(Request $request)
    {
        $service = $request->input('service');
        $query = IntegrationLog::where('status', 'failed')
            ->whereNull('resolved_at')
            ->where('retries', '<', self::MAX_RETRIES);

        if (!empty($service)) {
            $query->where('service', $service);
        }

        $logs = $query->take(25)->get();
        $count = 0;

        foreach ($logs as $log) {
            $order = $log->order_id ? Order::find($log->order_id) : null;
            if ($log->retry_job === IssueFactusInvoice::class && $order && !empty($order->invoice_cufe)) {
                $log->update(['resolved_at' => now()]);
                continue;
            }
            if ($log->retry_job === GenerateEnviaShipment::class && $order && !empty($order->tracking_number)) {
                $log->update(['resolved_at' => now()]);
                continue;
            }
            if ($this->dispatchRetryJob($log)) {
                $log->update(['resolved_at' => now(), 'retries' => $log->retries + 1]);
                $count++;
            }
        }

        AdminAuditLog::record('integration.bulk_retry', null, "Reintento masivo ejecutado ({$count} jobs encolados)" . ($service ? " para {$service}" : ''));

        return back()->with('success', "Reintento masivo completado: {$count} tarea(s) encoladas con validación de idempotencia.");
    }

    protected function dispatchRetryJob(IntegrationLog $log): mixed
    {
        return match ($log->retry_job) {
            GenerateEnviaShipment::class  => $log->order_id && GenerateEnviaShipment::dispatch($log->order_id),
            IssueFactusInvoice::class     => $log->order_id && IssueFactusInvoice::dispatch($log->order_id),
            SendOrderApprovedEmail::class => $log->order_id && SendOrderApprovedEmail::dispatch($log->order_id),
            SendOrderShippedEmail::class  => $log->order_id && SendOrderShippedEmail::dispatch($log->order_id),
            ProcessWompiEvent::class      => isset($log->request['transaction']) && ProcessWompiEvent::dispatch($log->request['transaction']),
            default                       => null,
        };
    }
}
