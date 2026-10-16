<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Mail\OrderReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Respuesta del cliente tras el pago (Frontend)
     */
    public function response(Request $request)
    {
        // PayU redirige aquí con parámetros GET
        // transactionState: 4 (Aprobado), 6 (Rechazado), 104 (Error), 7 (Pendiente)
        $state = $request->get('transactionState');
        $reference = $request->get('referenceCode');
        
        $status = 'pending';
        $message = 'Tu pago está siendo procesado.';

        if ($state == 4) {
            $status = 'success';
            $message = '¡Pago aprobado con éxito! Gracias por tu compra.';
        } elseif ($state == 6) {
            $status = 'error';
            $message = 'El pago fue rechazado. Intenta con otro medio.';
        }

        return view('payment.result', compact('status', 'message', 'reference'));
    }

    /**
     * Confirmación vía Webhook (Server to Server)
     */
    public function webhook(Request $request)
    {
        // PayU envía POST con parámetros
        Log::info('PayU Webhook received', $request->all());

        $reference = $request->post('reference_sale');
        $state = $request->post('state_pol'); // 4 Aprobado
        $value = $request->post('value');
        
        // Extraer ID de pedido de la referencia (ORDER-ID-TIME)
        $parts = explode('-', $reference);
        if (isset($parts[1])) {
            $orderId = $parts[1];
            $order = Order::find($orderId);

            if ($order) {
                if ($state == 4) {
                    $order->update(['payment_status' => 'paid', 'status' => 'processing']);
                    
                    // Enviar recibo por correo
                    try {
                        Mail::to($order->user->email)->send(new OrderReceipt($order->load('items.product', 'user')));
                    } catch (\Exception $e) {
                        Log::error('Error sending receipt email: ' . $e->getMessage());
                    }
                } elseif ($state == 6 || $state == 5) {
                    $order->update(['payment_status' => 'failed']);
                }
            }
        }

        return response('OK', 200);
    }
}
