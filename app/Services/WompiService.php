<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class WompiService
{
    /**
     * Firma de integridad: SHA256("{referencia}{monto_en_centavos}{moneda}{secreto_integridad}").
     */
    public function integritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference . $amountInCents . $currency . config('services.wompi.integrity_secret'));
    }

    /** URL del Web Checkout oficial de Wompi (sandbox o producción según llaves). */
    public function checkoutUrl(Order $order, string $redirectUrl): string
    {
        $cents = $order->totalInCents();

        return rtrim(config('services.wompi.checkout_url'), '/') . '/?' . http_build_query([
            'public-key'          => config('services.wompi.public_key'),
            'currency'            => $order->currency,
            'amount-in-cents'     => $cents,
            'reference'           => $order->reference,
            'signature:integrity' => $this->integritySignature($order->reference, $cents, $order->currency),
            'redirect-url'        => $redirectUrl,
            'customer-data:email' => $order->user->email,
            'customer-data:full-name' => $order->user->name,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Valida la autenticidad de un evento de Wompi.
     * checksum = SHA256(valores de signature.properties concatenados + timestamp + events_secret)
     */
    public function verifyEvent(array $payload, ?string $headerChecksum = null): bool
    {
        $secret = config('services.wompi.events_secret');
        $properties = Arr::get($payload, 'signature.properties');
        $checksum = Arr::get($payload, 'signature.checksum') ?: $headerChecksum;
        $timestamp = Arr::get($payload, 'timestamp');

        if (!$secret || !is_array($properties) || !$checksum || $timestamp === null) {
            return false;
        }

        $concat = '';
        foreach ($properties as $path) {
            $value = Arr::get($payload['data'] ?? [], $path);
            if ($value === null || is_array($value)) {
                return false;
            }
            $concat .= is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        $expected = hash('sha256', $concat . $timestamp . $secret);

        return hash_equals($expected, strtolower((string) $checksum));
    }

    /** Protección contra replay: el timestamp del evento debe estar dentro de la ventana permitida. */
    public function timestampIsFresh(array $payload): bool
    {
        $ts = (int) Arr::get($payload, 'timestamp', 0);
        if ($ts <= 0) {
            return false;
        }

        return abs(time() - $ts) <= config('services.wompi.timestamp_tolerance');
    }

    /** Consulta server-to-server del estado real de una transacción. */
    public function fetchTransaction(string $transactionId): array
    {
        $response = Http::baseUrl(config('services.wompi.api_url'))
            ->timeout(15)->retry(2, 300, throw: false)
            ->acceptJson()
            ->get('/transactions/' . urlencode($transactionId));

        $response->throw();

        return $response->json('data') ?? [];
    }
}
