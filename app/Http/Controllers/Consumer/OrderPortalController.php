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
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => null,
                'status' => 'processing',
                'status_label' => 'En empaque y preparación en la tienda local',
                'step' => 1,
                'events' => [
                    [
                        'status' => 'Pago confirmado exitosamente',
                        'description' => 'El comercio está preparando el paquete para entrega a Envia.com',
                        'date' => $order->created_at->format('d/m/Y'),
                        'time' => $order->created_at->format('h:i A'),
                        'location' => 'Fusagasugá',
                    ]
                ],
                'message' => 'Tu pedido está pagado y el comercio está alistando los productos para Envia.com.'
            ]);
        }

        try {
            $t = $envia->track($order->tracking_number);
            if (!empty($t['status']) && $t['status'] !== $order->tracking_status) {
                $order->update(['tracking_status' => $t['status']]);
            }
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'carrier' => strtoupper($order->shipping_carrier ?: 'Envia.com'),
                'status' => $t['status'] ?? $order->tracking_status,
                'status_label' => $t['status_label'] ?? 'En tránsito hacia tu dirección',
                'step' => $t['step'] ?? ($order->status === 'delivered' ? 5 : 3),
                'url' => $order->tracking_url,
                'destination' => $order->shipping_address,
                'events' => $t['events'] ?? [],
            ]);
        } catch (Throwable $e) {
            IntegrationLog::failure('envia', 'tracking', $order->id, $e);
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'carrier' => strtoupper($order->shipping_carrier ?: 'Envia.com'),
                'status' => $order->tracking_status ?? 'in_transit',
                'status_label' => 'En despacho con Envia.com',
                'step' => 3,
                'url' => $order->tracking_url,
                'destination' => $order->shipping_address,
                'events' => [
                    [
                        'status' => 'Guía emitida con Envia.com (' . strtoupper($order->shipping_carrier ?: 'Envia') . ')',
                        'description' => 'En despacho y trazabilidad activa',
                        'date' => $order->created_at->format('d/m/Y'),
                        'time' => $order->created_at->format('h:i A'),
                        'location' => 'Fusagasugá',
                    ]
                ],
            ]);
        }
    }
}
