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

        $req = Http::baseUrl($base ?? config('services.envia.api_url'))
            ->withToken($token)->acceptJson()->asJson()
            ->timeout(20)->retry(2, 500, throw: false);

        if (app()->environment('local', 'testing') || config('services.envia.env') === 'sandbox') {
            $req->withoutVerifying();
        }

        return $req;
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
        $city = $a['city'] ?? '';
        $postal = $a['postal_code'] ?? '';

        // Si es Colombia y city no es un código DANE de 8 dígitos, intentar resolverlo por código postal
        if (($a['country'] ?? 'CO') === 'CO' && !preg_match('/^\d{8}$/', (string)$city) && !empty($postal)) {
            $resolved = $this->resolveDaneCode($postal);
            if ($resolved) {
                $city = $resolved;
            }
        }

        $state = $a['state'] ?? '';
        // Envia.com para Colombia exige código de departamento ISO de 2 dígitos (ej: CU, DC, AN, VA)
        $stateCodes = [
            'cundinamarca' => 'CU',
            'bogota' => 'DC',
            'bogotá' => 'DC',
            'distrito capital' => 'DC',
            'antioquia' => 'AN',
            'valle' => 'VA',
            'valle del cauca' => 'VA',
            'santander' => 'SA',
            'norte de santander' => 'NS',
            'tolima' => 'TO',
            'boyaca' => 'BY',
            'boyacá' => 'BY',
            'meta' => 'ME',
            'huila' => 'HU',
            'caldas' => 'CA',
            'risaralda' => 'RI',
            'quindio' => 'QU',
            'quindío' => 'QU',
            'atlantico' => 'AT',
            'atlántico' => 'AT',
            'bolivar' => 'BL',
            'bolívar' => 'BL',
            'magdalena' => 'MA',
            'cesar' => 'CE',
            'cordoba' => 'CO',
            'córdoba' => 'CO',
            'narino' => 'NA',
            'nariño' => 'NA',
            'cauca' => 'CA',
        ];
        $normState = mb_strtolower(trim($state));
        if (isset($stateCodes[$normState])) {
            $state = $stateCodes[$normState];
        } elseif (strlen($state) > 3) {
            $state = strtoupper(substr($state, 0, 2));
        }

        return [
            'name' => $a['recipient'] ?? 'Cliente',
            'company' => '',
            'email' => $a['email'] ?? '',
            'phone' => $a['phone'] ?? '',
            'street' => $a['street'] ?? '',
            'number' => $a['number'] ?? 'S/N',
            'district' => $a['district'] ?? '',
            'city' => $city,
            'state' => $state,
            'country' => $a['country'] ?? 'CO',
            'postalCode' => $postal,
            'reference' => $a['reference'] ?? '',
        ];
    }

    /**
     * Resuelve el código DANE de 8 dígitos requerido por Envia.com en Colombia usando el código postal.
     */
    public function resolveDaneCode(string $postalCode): ?string
    {
        try {
            $res = Http::withoutVerifying()->timeout(5)->get('https://geocodes.envia.com/zipcode/CO/' . urlencode($postalCode));
            if ($res->successful()) {
                $data = $res->json('0');
                if (!empty($data['info']['stat_8digit'])) {
                    return (string) $data['info']['stat_8digit'];
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: fallback al valor original
        }

        return null;
    }

    /**
     * Cotización dinámica (POST /ship/rate/).
     * @return array<int,array{carrier:string,service:string,price:float,days:?int}>
     */
    public function quote(array $destinationAddress, array $package): array
    {
        // En sandbox sin token configurado: retornar cotizaciones simuladas para pruebas
        if (empty(config('services.envia.token')) && config('services.envia.env') === 'sandbox') {
            return [
                ['carrier' => 'servientrega', 'service' => 'standard', 'price' => 9500.0, 'days' => 2],
                ['carrier' => 'interrapidisimo', 'service' => 'express', 'price' => 11200.0, 'days' => 1],
                ['carrier' => 'coordinadora', 'service' => 'standard', 'price' => 10400.0, 'days' => 2],
            ];
        }

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

        // Sin token configurado o en entorno sandbox/testing sin token: generar guía simulada
        if (empty(config('services.envia.token'))) {
            $tracking = 'SANDBOX-' . strtoupper(substr(md5($order->reference . time()), 0, 10));
            return [
                'payload' => $payload,
                'tracking_number' => $tracking,
                'label_url' => 'https://api-test.envia.com/labels/' . $tracking . '.pdf',
                'tracking_url' => 'https://envia.com/rastreo?label=' . $tracking,
                'carrier' => $order->shipping_carrier ?: 'servientrega',
                'raw' => ['sandbox' => true, 'mock' => true],
            ];
        }

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
        // En sandbox sin token: eventos simulados enriquecidos para pruebas
        if (empty(config('services.envia.token')) && config('services.envia.env') === 'sandbox') {
            return [
                'status' => 'in_transit',
                'status_label' => 'En tránsito hacia el destino',
                'step' => 3, // 1: Pagado, 2: Guía Generada, 3: En tránsito, 4: En reparto, 5: Entregado
                'events' => [
                    [
                        'status' => 'Guía oficial Envia.com generada en Fusagasugá',
                        'description' => 'Rotulado y empaque completado para recolección',
                        'date' => now()->subHours(5)->format('d/m/Y'),
                        'time' => now()->subHours(5)->format('h:i A'),
                        'location' => 'Centro Operativo Fusagasugá',
                    ],
                    [
                        'status' => 'Paquete recolectado por transportadora aliada',
                        'description' => 'Recibido en centro de acopio regional del Sumapaz',
                        'date' => now()->subHours(3)->format('d/m/Y'),
                        'time' => now()->subHours(3)->format('h:i A'),
                        'location' => 'Fusagasugá, Cundinamarca',
                    ],
                    [
                        'status' => 'En tránsito en ruta de distribución local',
                        'description' => 'En móvil de transporte hacia el cuadrante de destino',
                        'date' => now()->subHour()->format('d/m/Y'),
                        'time' => now()->subHour()->format('h:i A'),
                        'location' => 'Región del Sumapaz',
                    ],
                ],
                'raw' => ['sandbox' => true],
            ];
        }

        $res = $this->client(config('services.envia.api_url'))->post('/ship/generaltrack/', [
            'trackingNumbers' => [$trackingNumber],
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Envia /tracking falló (' . $res->status() . ')');
        }

        $d = $res->json('data.0') ?? [];
        $rawStatus = strtolower((string)($d['status'] ?? ''));

        // Normalizar etapa visual (1 a 5)
        $step = 3;
        $statusLabel = 'En tránsito';
        if (str_contains($rawStatus, 'deliver') || str_contains($rawStatus, 'entreg')) {
            $step = 5;
            $statusLabel = 'Entregado con éxito';
        } elseif (str_contains($rawStatus, 'out_for_delivery') || str_contains($rawStatus, 'reparto')) {
            $step = 4;
            $statusLabel = 'En reparto local a domicilio';
        } elseif (str_contains($rawStatus, 'transit') || str_contains($rawStatus, 'transito') || str_contains($rawStatus, 'shipped')) {
            $step = 3;
            $statusLabel = 'En tránsito hacia el destino';
        } elseif (str_contains($rawStatus, 'picked') || str_contains($rawStatus, 'recolect') || str_contains($rawStatus, 'label')) {
            $step = 2;
            $statusLabel = 'Guía generada / Recolectado';
        }

        $events = [];
        foreach (($d['eventHistory'] ?? []) as $ev) {
            $events[] = [
                'status' => $ev['description'] ?? ($ev['status'] ?? 'Novedad de envío'),
                'description' => $ev['comment'] ?? '',
                'date' => isset($ev['date']) ? date('d/m/Y', strtotime($ev['date'])) : now()->format('d/m/Y'),
                'time' => isset($ev['date']) ? date('h:i A', strtotime($ev['date'])) : now()->format('h:i A'),
                'location' => $ev['location'] ?? ($ev['city'] ?? 'Fusagasugá'),
            ];
        }

        return [
            'status' => $d['status'] ?? 'in_transit',
            'status_label' => $statusLabel,
            'step' => $step,
            'events' => $events,
            'raw' => $res->json(),
        ];
    }
}
