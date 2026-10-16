<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FactusService
{
    private const DOC_TYPES = ['RC' => 1, 'TI' => 2, 'CC' => 3, 'TE' => 4, 'CE' => 5, 'NIT' => 6, 'PP' => 7];

    private function token(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            Cache::forget('factus.access_token');
        }

        return Cache::remember('factus.access_token', 50 * 60, function () {
            $cfg = config('services.factus');
            $res = Http::baseUrl($cfg['api_url'])->asForm()->acceptJson()->timeout(20)->post('/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $cfg['client_id'],
                'client_secret' => $cfg['client_secret'],
                'username' => $cfg['username'],
                'password' => $cfg['password'],
            ]);

            if ($res->failed() || !$res->json('access_token')) {
                throw new RuntimeException('Factus OAuth falló (' . $res->status() . ').');
            }

            return $res->json('access_token');
        });
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.factus.api_url'))
            ->withToken($this->token())->acceptJson()->asJson()
            ->timeout(60)
            ->retry(1, 300, function ($e, $req) {
                // Token vencido: renovar y reintentar una vez
                if (method_exists($e, 'response') && $e->response?->status() === 401) {
                    $req->withToken($this->token(true));
                    return true;
                }
                return false;
            }, throw: false);
    }

    /** Payload JSON de factura electrónica con desglose de IVA por ítem. */
    public function buildPayload(Order $order): array
    {
        $order->loadMissing('items.product', 'user');
        $cfg = config('services.factus');
        $snap = $order->shipping_snapshot ?? [];
        $rate = (float) $cfg['iva_rate'];
        $gross = (float) $order->subtotal + (float) $order->shipping_cost;
        // Descuento (puntos) repartido proporcionalmente
        $discountRate = $gross > 0 ? round(((float) $order->discount / $gross) * 100, 2) : 0;

        $docType = strtoupper($order->customer_document_type ?? 'CC');
        $isCompany = $docType === 'NIT';

        $lines = [];
        foreach ($order->items as $item) {
            $taxed = $item->product?->is_taxed ?? true;
            $lines[] = $this->line(
                'P' . $item->product_id,
                (string) ($item->product?->name ?? 'Producto'),
                (int) $item->quantity,
                (float) $item->price,
                $taxed,
                $discountRate,
                $rate,
                (bool) $cfg['prices_include_tax']
            );
        }
        if ((float) $order->shipping_cost > 0) {
            $lines[] = $this->line('FLETE', 'Servicio de envío', 1, (float) $order->shipping_cost, true, $discountRate, $rate, (bool) $cfg['prices_include_tax']);
        }

        return [
            'numbering_range_id' => (int) $cfg['numbering_range_id'],
            'reference_code' => $order->reference,
            'observation' => 'Pedido ' . $order->reference,
            'payment_form' => '1',
            'payment_method_code' => '48', // tarjeta crédito (Wompi)
            'customer' => [
                'identification_document_id' => self::DOC_TYPES[$docType] ?? 3,
                'identification' => (string) $order->customer_document,
                'names' => $order->user->name,
                'address' => trim(($snap['street'] ?? '') . ' ' . ($snap['number'] ?? '')) ?: $order->shipping_address,
                'email' => $order->user->email,
                'phone' => $snap['phone'] ?? $order->user->phone,
                'legal_organization_id' => $isCompany ? '1' : '2',
                'tribute_id' => '21',
                'municipality_id' => (string) ($cfg['default_municipality_id'] ?? 149),
            ],
            'items' => $lines,
        ];
    }

    private function line(string $code, string $name, int $qty, float $price, bool $taxed, float $discountRate, float $rate, bool $includesTax): array
    {
        $unit = $taxed && !$includesTax ? round($price * (1 + $rate / 100), 2) : $price;

        return [
            'code_reference' => $code,
            'name' => mb_substr($name, 0, 250),
            'quantity' => $qty,
            'discount_rate' => $discountRate,
            'price' => $unit,
            'tax_rate' => $taxed ? number_format($rate, 2, '.', '') : '0.00',
            'unit_measure_id' => (int) (config('services.factus.unit_measure_id') ?? 70),
            'standard_code_id' => 1,
            'is_excluded' => $taxed ? 0 : 1,
            'tribute_id' => 1, // IVA
        ];
    }

    /** Emite la factura (POST /v1/bills/validate). */
    public function issue(Order $order): array
    {
        $payload = $this->buildPayload($order);
        $res = $this->client()->post('/v1/bills/validate', $payload);

        if ($res->failed()) {
            throw new RuntimeException('Factus validate falló (' . $res->status() . '): ' . mb_substr($res->body(), 0, 400));
        }

        $bill = $res->json('data.bill') ?? [];
        if (empty($bill['cufe']) || empty($bill['number'])) {
            throw new RuntimeException('Factus no devolvió CUFE/número de factura.');
        }

        return ['payload' => $payload, 'number' => $bill['number'], 'cufe' => $bill['cufe'], 'raw' => $res->json()];
    }

    /** Descarga PDF (base64 decodificado). */
    public function downloadPdf(string $number): string
    {
        return $this->download("/v1/bills/download-pdf/{$number}", 'pdf_base_64_encoded');
    }

    public function downloadXml(string $number): string
    {
        return $this->download("/v1/bills/download-xml/{$number}", 'xml_base_64_encoded');
    }

    private function download(string $path, string $key): string
    {
        $res = $this->client()->get($path);
        $b64 = $res->json("data.{$key}");
        if ($res->failed() || !$b64) {
            throw new RuntimeException("Factus descarga falló ({$path}, {$res->status()}).");
        }

        $bin = base64_decode($b64, true);
        if ($bin === false) {
            throw new RuntimeException('Factus devolvió base64 inválido.');
        }

        return $bin;
    }
}
