<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWompiEvent;
use App\Models\IntegrationLog;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WompiWebhookController extends Controller
{
    public function __invoke(Request $request, WompiService $wompi): JsonResponse
    {
        $payload = $request->json()->all();

        // 1) Autenticidad criptográfica
        if (!$wompi->verifyEvent($payload, $request->header('X-Event-Checksum'))) {
            IntegrationLog::failure('wompi', 'webhook.invalid_signature', null, 'Firma de evento inválida', ['ip' => $request->ip()]);
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        // 2) Anti-replay: timestamp dentro de ventana (la firma incluye el timestamp, no puede alterarse)
        if (!$wompi->timestampIsFresh($payload)) {
            IntegrationLog::failure('wompi', 'webhook.stale_timestamp', null, 'Timestamp fuera de ventana', ['timestamp' => $payload['timestamp'] ?? null]);
            return response()->json(['error' => 'stale_timestamp'], 400);
        }

        // 3) Solo transaction.updated; otros eventos se reconocen sin procesar
        if (($payload['event'] ?? null) !== 'transaction.updated') {
            return response()->json(['status' => 'ignored']);
        }

        $tx = $payload['data']['transaction'] ?? null;
        if (!is_array($tx)) {
            return response()->json(['error' => 'malformed'], 422);
        }

        // 4) Procesamiento asíncrono (idempotente por transaction_id dentro del job)
        ProcessWompiEvent::dispatch($tx);

        return response()->json(['status' => 'queued']);
    }
}
