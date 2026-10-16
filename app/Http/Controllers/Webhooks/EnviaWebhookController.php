<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderShippedEmail;
use App\Models\IntegrationLog;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnviaWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.envia.webhook_secret');
        $given = (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret', ''));

        if ($secret === '' || !hash_equals($secret, $given)) {
            IntegrationLog::failure('envia', 'webhook.unauthorized', null, 'Secreto de webhook inválido', ['ip' => $request->ip()]);
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $tracking = (string) ($request->input('data.trackingNumber') ?? $request->input('trackingNumber', ''));
        $status = strtolower((string) ($request->input('data.status') ?? $request->input('status', '')));

        $order = $tracking !== '' ? Order::where('tracking_number', $tracking)->first() : null;
        if (!$order) {
            return response()->json(['status' => 'ignored']);
        }

        $update = ['tracking_status' => $status];
        $firstShipment = false;

        if (str_contains($status, 'deliver') || str_contains($status, 'entreg')) {
            $update += ['status' => 'delivered', 'delivered_at' => now()];
        } elseif ($order->status === 'processing' && (str_contains($status, 'transit') || str_contains($status, 'ship') || str_contains($status, 'picked') || str_contains($status, 'recolect'))) {
            $update += ['status' => 'shipped', 'shipped_at' => now()];
            $firstShipment = true;
        }

        $order->update($update);
        IntegrationLog::success('envia', 'webhook.tracking', $order->id, ['tracking' => $tracking, 'status' => $status]);

        if ($firstShipment) {
            SendOrderShippedEmail::dispatch($order->id);
        }

        return response()->json(['status' => 'ok']);
    }
}
