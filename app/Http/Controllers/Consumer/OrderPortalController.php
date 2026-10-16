<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Services\EnviaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OrderPortalController extends Controller
{
    private function mine(int $id): Order
    {
        return Order::where('user_id', Auth::id())->findOrFail($id);
    }

    public function invoice(int $id, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xml'], true), 404);
        $order = $this->mine($id);
        $path = $format === 'pdf' ? $order->invoice_pdf_path : $order->invoice_xml_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'La factura aún no está disponible.');

        return Storage::disk('local')->download($path, "factura-{$order->invoice_number}.{$format}");
    }

    /** Estado de rastreo en vivo consultando Envia.com. */
    public function tracking(int $id, EnviaService $envia): JsonResponse
    {
        $order = $this->mine($id);
        if (!$order->tracking_number) {
            return response()->json(['message' => 'Tu pedido aún no tiene guía.'], 404);
        }

        try {
            $t = $envia->track($order->tracking_number);
            if ($t['status'] && $t['status'] !== $order->tracking_status) {
                $order->update(['tracking_status' => $t['status']]);
            }
            return response()->json([
                'tracking_number' => $order->tracking_number,
                'carrier' => $order->shipping_carrier,
                'status' => $t['status'] ?? $order->tracking_status,
                'url' => $order->tracking_url,
                'events' => $t['events'],
            ]);
        } catch (Throwable $e) {
            IntegrationLog::failure('envia', 'tracking', $order->id, $e);
            return response()->json(['message' => 'No pudimos consultar el rastreo ahora.', 'url' => $order->tracking_url], 502);
        }
    }
}
