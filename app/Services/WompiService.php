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

    public function isSandboxWithoutKeys(): bool
    {
        return config('services.wompi.env', 'sandbox') === 'sandbox'
            && empty(config('services.wompi.public_key'));
    }

    /** URL del Web Checkout oficial de Wompi (sandbox o producción según llaves, o simulador local si no hay llaves). */
    public function checkoutUrl(Order $order, string $redirectUrl): string
    {
        $cents = $order->totalInCents();
        $signature = $this->integritySignature($order->reference, $cents, $order->currency);

        if ($this->isSandboxWithoutKeys()) {
            return route('wompi.sandbox.checkout', [
                'reference'       => $order->reference,
                'amount_in_cents' => $cents,
                'currency'        => $order->currency,
                'signature'       => $signature,
                'redirect_url'    => $redirectUrl,
            ]);
        }

        return rtrim(config('services.wompi.checkout_url'), '/') . '/?' . $this->buildCheckoutQuery([
            'public-key'              => config('services.wompi.public_key'),
            'currency'                => $order->currency,
            'amount-in-cents'         => $cents,
            'reference'               => $order->reference,
            'signature:integrity'     => $signature,
            'redirect-url'            => $redirectUrl,
            'customer-data:email'     => $order->user->email,
            'customer-data:full-name' => $order->user->name,
        ]);
    }

    /** URL de Checkout Wompi para pago de pauta publicitaria (BannerRequest). */
    public function bannerCheckoutUrl(\App\Models\BannerRequest $bannerRequest, string $reference, string $redirectUrl): string
    {
        $cents = (int) round(((float) $bannerRequest->cost) * 100);
        $signature = $this->integritySignature($reference, $cents, 'COP');

        if ($this->isSandboxWithoutKeys()) {
            return route('wompi.sandbox.checkout', [
                'reference'       => $reference,
                'amount_in_cents' => $cents,
                'currency'        => 'COP',
                'signature'       => $signature,
                'redirect_url'    => $redirectUrl,
            ]);
        }

        return rtrim(config('services.wompi.checkout_url'), '/') . '/?' . $this->buildCheckoutQuery([
            'public-key'              => config('services.wompi.public_key'),
            'currency'                => 'COP',
            'amount-in-cents'         => $cents,
            'reference'               => $reference,
            'signature:integrity'     => $signature,
            'redirect-url'            => $redirectUrl,
            'customer-data:email'     => $bannerRequest->user?->email ?? '',
            'customer-data:full-name' => $bannerRequest->user?->name ?? '',
        ]);
    }

    /**
     * El checkout de Wompi rechaza (403 CloudFront) un redirect-url que apunta a localhost.
     * En ese caso se omite el parámetro; en producción/túnel https se envía normalmente.
     */
    private function buildCheckoutQuery(array $params): string
    {
        $host = strtolower((string) parse_url((string) ($params['redirect-url'] ?? ''), PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]', ''], true)) {
            unset($params['redirect-url']);
        }

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
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
        if (str_starts_with($transactionId, 'sbx_') || $this->isSandboxWithoutKeys()) {
            $record = \App\Models\PaymentTransaction::where('transaction_id', $transactionId)->first();
            if ($record) {
                return [
                    'id'              => $record->transaction_id,
                    'status'          => $record->status,
                    'reference'       => $record->reference,
                    'amount_in_cents' => (int) $record->amount_in_cents,
                    'currency'        => $record->currency ?: 'COP',
                ];
            }
        }

        $response = Http::baseUrl(config('services.wompi.api_url'))
            ->timeout(15)->retry(2, 300, throw: false)
            ->acceptJson()
            ->get('/transactions/' . urlencode($transactionId));

        $response->throw();

        return $response->json('data') ?? [];
    }
}
