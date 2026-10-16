<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingAddress;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EnviaService
{
    private function client(?string $base = null): PendingRequest
    {
        $token = config('services.envia.token');
        if (!$token) {
            throw new RuntimeException('ENVIA_API_TOKEN no configurado.');
        }

        return Http::baseUrl($base ?? config('services.envia.api_url'))
            ->withToken($token)->acceptJson()->asJson()
            ->timeout(20)->retry(2, 500, throw: false);
    }

    /**
     * Construye paquetes a partir de ítems (colección con ->product y ->quantity).
     * Se agrega en 1 paquete: peso real sumado y dimensiones volumétricas aproximadas
     * (volumen total -> cubo equivalente, mínimo la mayor arista individual).
     */
    public function buildPackage(iterable $items, float $declaredValue): array
    {
        $weightKg = 0.0;
        $volumeCm3 = 0.0;
        $maxEdge = [0, 0, 0];
        [$dl, $dw, $dh] = config('services.envia.default_dims_cm');

        foreach ($items as $item) {
            /** @var Product $p */
            $p = $item->product;
            $qty = (int) $item->quantity;
            $kg = $p->weight_grams ? $p->weight_grams / 1000 : config('services.envia.default_weight_kg');
            $l = $p->length_cm ?: $dl;
            $w = $p->width_cm ?: $dw;
            $h = $p->height_cm ?: $dh;

            $weightKg += $kg * $qty;
            $volumeCm3 += $l * $w * $h * $qty;
            $edges = [$l, $w, $h];
            rsort($edges);
            foreach ($edges as $i => $e) {
                $maxEdge[$i] = max($maxEdge[$i], $e);
            }
        }

        $side = (int) ceil(pow(max($volumeCm3, 1), 1 / 3));
        $length = max($side, $maxEdge[0]);
        $width = max($side, $maxEdge[1]);
        $height = max($side, $maxEdge[2]);

        // Peso facturable = máx(real, volumétrico 5000 cm3/kg)
        $volumetricKg = ($length * $width * $height) / 5000;

        return [
            'content' => 'Mercancía FusaShop',
            'amount' => 1,
            'type' => 'box',
            'dimensions' => ['length' => $length, 'width' => $width, 'height' => $height],
            'weight' => round(max($weightKg, $volumetricKg, 0.1), 2),
            'insurance' => 0,
            'declaredValue' => round($declaredValue, 0),
            'weightUnit' => 'KG',
            'lengthUnit' => 'CM',
        ];
    }

    private function destination(array $a): array
    {
        return [
            'name' => $a['recipient'] ?? 'Cliente',
            'company' => '',
            'email' => $a['email'] ?? '',
            'phone' => $a['phone'] ?? '',
            'street' => $a['street'] ?? '',
            'number' => $a['number'] ?? 'S/N',
            'district' => $a['district'] ?? '',
            'city' => $a['city'] ?? '',
            'state' => $a['state'] ?? '',
            'country' => $a['country'] ?? 'CO',
            'postalCode' => $a['postal_code'] ?? '',
            'reference' => $a['reference'] ?? '',
        ];
    }

    /**
     * Cotización dinámica (POST /ship/rate/).
     * @return array<int,array{carrier:string,service:string,price:float,days:?int}>
     */
    public function quote(array $destinationAddress, array $package): array
    {
        $carriers = array_filter(array_map('trim', explode(',', (string) config('services.envia.default_carrier'))));
        $rates = [];

        foreach ($carriers as $carrier) {
            $payload = [
                'origin' => config('services.envia.origin'),
                'destination' => $this->destination($destinationAddress),
                'packages' => [$package],
                'shipment' => ['carrier' => $carrier, 'type' => 1],
                'settings' => ['currency' => 'COP'],
            ];

            $res = $this->client()->post('/ship/rate/', $payload);
            if ($res->failed() || $res->json('meta') === 'error') {
                throw new RuntimeException('Envia /rate falló (' . $res->status() . '): ' . mb_substr($res->body(), 0, 300));
            }

            foreach ((array) $res->json('data', []) as $r) {
                $rates[] = [
                    'carrier' => (string) ($r['carrier'] ?? $carrier),
                    'service' => (string) ($r['service'] ?? 'standard'),
                    'price' => (float) ($r['totalPrice'] ?? 0),
                    'days' => isset($r['deliveryEstimate']) ? (int) filter_var($r['deliveryEstimate'], FILTER_SANITIZE_NUMBER_INT) : null,
                ];
            }
        }

        usort($rates, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $rates;
    }

    /**
     * Generación de guía (POST /ship/generate/). Devuelve tracking, label y carrier.
     */
    public function generate(Order $order): array
    {
        $order->loadMissing('items.product', 'user');
        $snapshot = $order->shipping_snapshot ?? [];
        $snapshot['email'] = $snapshot['email'] ?? $order->user->email;

        $payload = [
            'origin' => config('services.envia.origin'),
            'destination' => $this->destination($snapshot),
            'packages' => [$this->buildPackage($order->items, (float) $order->subtotal)],
            'shipment' => [
                'carrier' => $order->shipping_carrier,
                'service' => $order->shipping_service,
                'type' => 1,
            ],
            'settings' => ['printFormat' => 'PDF', 'printSize' => 'STOCK_4X6', 'currency' => 'COP', 'comments' => $order->reference],
        ];

        $res = $this->client()->post('/ship/generate/', $payload);
        if ($res->failed() || $res->json('meta') === 'error') {
            throw new RuntimeException('Envia /generate falló (' . $res->status() . '): ' . mb_substr($res->body(), 0, 300));
        }

        $data = $res->json('data.0') ?? $res->json('data') ?? [];
        if (empty($data['trackingNumber'])) {
            throw new RuntimeException('Envia /generate no devolvió trackingNumber.');
        }

        return [
            'payload' => $payload,
            'tracking_number' => (string) $data['trackingNumber'],
            'label_url' => $data['label'] ?? null,
            'tracking_url' => $data['trackUrl'] ?? ('https://envia.com/rastreo?label=' . $data['trackingNumber']),
            'carrier' => $data['carrier'] ?? $order->shipping_carrier,
            'raw' => $res->json(),
        ];
    }

    /** Rastreo en tiempo real (POST /ship/generaltrack/). */
    public function track(string $trackingNumber): array
    {
        $res = $this->client(config('services.envia.api_url'))->post('/ship/generaltrack/', [
            'trackingNumbers' => [$trackingNumber],
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Envia /tracking falló (' . $res->status() . ')');
        }

        $d = $res->json('data.0') ?? [];

        return [
            'status' => $d['status'] ?? null,
            'events' => $d['eventHistory'] ?? [],
            'raw' => $res->json(),
        ];
    }
}
