<?php

namespace App\Http\Controllers\Consumer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWompiEvent;
use App\Models\IntegrationLog;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\ShippingAddress;
use App\Services\CheckoutService;
use App\Services\EnviaService;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CheckoutController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $cart = $user->cart()->with('items.product')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('consumer.cart');
        }

        $merchantIds = $cart->items->pluck('product.merchant_id')->unique();
        $availablePoints = LoyaltyPoint::where('user_id', $user->id)->whereIn('merchant_id', $merchantIds)->sum('points');
        $addresses = $user->shippingAddresses()->orderByDesc('is_default')->get();

        if ($addresses->isEmpty()) {
            return redirect()->route('consumer.addresses')
                ->with('error', 'Agrega una dirección de envío para poder cotizar tu flete.');
        }

        return view('consumer.checkout', compact('cart', 'availablePoints', 'addresses'));
    }

    /** Cotización dinámica de flete por dirección + carrito (peso/dimensiones volumétricas). */
    public function quote(Request $request, EnviaService $envia): JsonResponse
    {
        $data = $request->validate(['address_id' => 'required|integer']);
        $address = Auth::user()->shippingAddresses()->findOrFail($data['address_id']);

        try {
            return response()->json(['rates' => $this->rates($envia, $address)]);
        } catch (Throwable $e) {
            IntegrationLog::failure('envia', 'rate', null, $e, ['postal_code' => $address->postal_code]);
            return response()->json(['message' => 'No pudimos cotizar el envío en este momento. Intenta de nuevo.'], 502);
        }
    }

    private function rates(EnviaService $envia, ShippingAddress $address): array
    {
        $cart = Auth::user()->cart()->with('items.product')->first();
        abort_if(!$cart || $cart->items->isEmpty(), 422, 'Carrito vacío');

        $package = $envia->buildPackage($cart->items, (float) $cart->total());
        $snapshot = $address->toSnapshot() + ['email' => Auth::user()->email];

        return $envia->quote($snapshot, $package);
    }

    public function process(Request $request, EnviaService $envia, CheckoutService $checkout, WompiService $wompi)
    {
        $data = $request->validate([
            'address_id' => 'required|integer',
            'shipping_rate' => ['required', 'string', 'regex:/^[a-z0-9_\-]+\|[A-Za-z0-9_\-\. ]+$/i'],
            'document_type' => 'required|in:CC,CE,NIT,PP,TI,TE',
            'document' => ['required', 'string', 'regex:/^[A-Za-z0-9\-]{5,20}$/'],
            'use_points' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $cart = $user->cart()->with('items.product')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('consumer.cart');
        }
        $address = $user->shippingAddresses()->findOrFail($data['address_id']);

        // El precio del flete se re-cotiza en servidor: nunca se confía en el valor enviado por el cliente.
        [$carrier, $service] = explode('|', $data['shipping_rate'], 2);
        try {
            $rate = collect($this->rates($envia, $address))
                ->first(fn ($r) => strtolower($r['carrier']) === strtolower($carrier) && $r['service'] === $service);
        } catch (Throwable $e) {
            IntegrationLog::failure('envia', 'rate', null, $e);
            return back()->withInput()->with('error', 'No pudimos validar el costo de envío. Intenta de nuevo.');
        }
        if (!$rate) {
            return back()->withInput()->with('error', 'La tarifa de envío seleccionada ya no está disponible. Cotiza nuevamente.');
        }

        try {
            $order = $checkout->createOrder(
                $user, $cart, $address, $rate, $data['document_type'], strtoupper($data['document']), $request->boolean('use_points')
            );
        } catch (InsufficientStockException $e) {
            return redirect()->route('consumer.cart')->with('error', $e->getMessage());
        }

        return redirect()->away($wompi->checkoutUrl($order, route('consumer.checkout.result')));
    }

    /**
     * Retorno desde Wompi (?id=transaction_id). La fuente de verdad sigue siendo el webhook; aquí solo se
     * reconcilia consultando la API de Wompi (server-to-server) por si el webhook aún no llegó.
     */
    public function result(Request $request, WompiService $wompi)
    {
        $transactionId = (string) $request->query('id', '');
        $order = null;

        if ($transactionId !== '') {
            try {
                $tx = $wompi->fetchTransaction($transactionId);
                $order = Order::where('user_id', Auth::id())->where('reference', $tx['reference'] ?? '')->first();
                if ($order && $order->payment_status === 'pending') {
                    ProcessWompiEvent::dispatch($tx);
                }
            } catch (Throwable $e) {
                IntegrationLog::failure('wompi', 'reconcile', null, $e, ['transaction_id' => $transactionId]);
            }
        }

        $order ??= Order::where('user_id', Auth::id())->latest()->first();
        abort_if(!$order, 404);

        [$status, $message] = match (true) {
            $order->payment_status === 'paid' => ['success', '¡Pago aprobado! Estamos preparando tu pedido y tu factura electrónica.'],
            in_array($order->payment_status, ['failed'], true) => ['error', 'El pago no fue aprobado. Las unidades reservadas fueron liberadas.'],
            default => ['pending', 'Estamos confirmando tu pago con Wompi. Esta página se actualizará sola.'],
        };

        return view('payment.result', ['status' => $status, 'message' => $message, 'reference' => $order->reference]);
    }
}
