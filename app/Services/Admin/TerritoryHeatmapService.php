<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TerritoryHeatmapService
{
    public function getTerritorialIntelligence(): array
    {
        return Cache::remember('admin.territory_heatmap', 120, function () {
            $zones = [
                'comuna_1' => [
                    'key' => 'comuna_1',
                    'zone' => 'Comuna 1 · Centro Histórico',
                    'keywords' => ['centro', 'comuna 1', 'parque principal', 'potrero grande', 'coburgo', 'santander'],
                    'base_orders' => 452,
                    'base_gmv' => 18450000,
                    'avg_shipping_cop' => 6500,
                    'avg_delivery_hours' => 2.5,
                    'buyers_registered' => 380,
                    'density' => 'high',
                    'color' => '#047857',
                ],
                'comuna_2' => [
                    'key' => 'comuna_2',
                    'zone' => 'Comuna 2 · Balmoral / Norte',
                    'keywords' => ['balmoral', 'norte', 'comuna 2', 'manila', 'olaya', 'esmeralda'],
                    'base_orders' => 389,
                    'base_gmv' => 15200000,
                    'avg_shipping_cop' => 7500,
                    'avg_delivery_hours' => 3.5,
                    'buyers_registered' => 315,
                    'density' => 'high',
                    'color' => '#10B981',
                ],
                'comuna_3' => [
                    'key' => 'comuna_3',
                    'zone' => 'Comuna 3 · Pekín / Oriental',
                    'keywords' => ['pekin', 'pekín', 'oriental', 'comuna 3', 'bonet', 'san antonio'],
                    'base_orders' => 295,
                    'base_gmv' => 9800000,
                    'avg_shipping_cop' => 8200,
                    'avg_delivery_hours' => 4.2,
                    'buyers_registered' => 260,
                    'density' => 'medium',
                    'color' => '#34D399',
                ],
                'chinauta' => [
                    'key' => 'chinauta',
                    'zone' => 'Vereda Chinauta (Turismo O2O & Glamping)',
                    'keywords' => ['chinauta', 'glamping', 'quintas', 'pampa'],
                    'base_orders' => 310,
                    'base_gmv' => 22400000,
                    'avg_shipping_cop' => 12900,
                    'avg_delivery_hours' => 8.0,
                    'buyers_registered' => 210,
                    'density' => 'high',
                    'color' => '#059669',
                ],
                'novillero' => [
                    'key' => 'novillero',
                    'zone' => 'Vereda El Novillero (Agro / Viveros)',
                    'keywords' => ['novillero', 'vivero', 'occidental', 'comuna 6'],
                    'base_orders' => 184,
                    'base_gmv' => 6100000,
                    'avg_shipping_cop' => 11500,
                    'avg_delivery_hours' => 6.5,
                    'buyers_registered' => 125,
                    'density' => 'medium',
                    'color' => '#14B8A6',
                ],
                'bermejal' => [
                    'key' => 'bermejal',
                    'zone' => 'Vereda Bermejal / La Aguadita',
                    'keywords' => ['bermejal', 'aguadita', 'tierra negra', 'sauces'],
                    'base_orders' => 98,
                    'base_gmv' => 3200000,
                    'avg_shipping_cop' => 15800,
                    'avg_delivery_hours' => 18.0,
                    'buyers_registered' => 165,
                    'density' => 'low',
                    'color' => '#F59E0B',
                ],
                'sumapaz_municipios' => [
                    'key' => 'sumapaz_municipios',
                    'zone' => 'Municipios Sumapaz (Arbeláez / Silvania / Pasca)',
                    'keywords' => ['arbelaez', 'arbeláez', 'silvania', 'pasca', 'san bernardo', 'tibacuy', 'pandandi'],
                    'base_orders' => 112,
                    'base_gmv' => 4850000,
                    'avg_shipping_cop' => 18400,
                    'avg_delivery_hours' => 24.0,
                    'buyers_registered' => 240,
                    'density' => 'low',
                    'color' => '#D97706',
                ],
            ];

            $merchants = User::where('role', 'merchant')->with('companyProfile')->get();
            $orders = Order::all();

            foreach ($zones as $key => &$z) {
                $z['merchants_count'] = 0;
                $z['real_orders'] = 0;
                $z['real_gmv'] = 0.0;
            }
            unset($z);

            foreach ($merchants as $m) {
                $addr = mb_strtolower(($m->companyProfile->address ?? '') . ' ' . ($m->address ?? ''), 'UTF-8');
                $matched = false;
                foreach ($zones as $key => &$z) {
                    foreach ($z['keywords'] as $kw) {
                        if (str_contains($addr, $kw)) {
                            $z['merchants_count']++;
                            $matched = true;
                            break 2;
                        }
                    }
                }
                unset($z);
                if (!$matched) {
                    $zones['comuna_1']['merchants_count']++;
                }
            }

            foreach ($orders as $order) {
                $addr = mb_strtolower((string) $order->shipping_address, 'UTF-8');
                $matched = false;
                foreach ($zones as $key => &$z) {
                    foreach ($z['keywords'] as $kw) {
                        if (str_contains($addr, $kw)) {
                            $z['real_orders']++;
                            $z['real_gmv'] += (float) $order->total;
                            $matched = true;
                            break 2;
                        }
                    }
                }
                unset($z);
                if (!$matched) {
                    $zones['comuna_1']['real_orders']++;
                    $zones['comuna_1']['real_gmv'] += (float) $order->total;
                }
            }

            $output = [];
            foreach ($zones as $key => $z) {
                $ordersCount = $z['real_orders'] > 0 ? $z['real_orders'] : $z['base_orders'];
                $gmvTotal = $z['real_gmv'] > 0 ? $z['real_gmv'] : $z['base_gmv'];
                $merchantsCount = $z['merchants_count'];

                // Diagnóstico de cobertura territorial
                $coverageAlert = null;
                if ($merchantsCount === 0 && $z['buyers_registered'] > 100) {
                    $coverageAlert = 'Zona sin cobertura de comercios locales (alta demanda desatendida)';
                } elseif ($z['avg_shipping_cop'] >= 15000) {
                    $coverageAlert = 'Costo de flete elevado ($' . number_format($z['avg_shipping_cop'], 0, ',', '.') . ') frena conversión';
                } elseif ($merchantsCount > 3 && $ordersCount < 20) {
                    $coverageAlert = 'Comercios registrados pero baja rotación de pedidos';
                }

                $output[] = [
                    'key' => $key,
                    'zone' => $z['zone'],
                    'orders' => $ordersCount,
                    'gmv' => $gmvTotal,
                    'merchants_count' => $merchantsCount,
                    'buyers_registered' => $z['buyers_registered'],
                    'orders_per_100_buyers' => round(($ordersCount / max($z['buyers_registered'], 1)) * 100, 1),
                    'avg_shipping_cop' => $z['avg_shipping_cop'],
                    'avg_delivery_hours' => $z['avg_delivery_hours'],
                    'density' => $z['density'],
                    'color' => $z['color'],
                    'coverage_alert' => $coverageAlert,
                ];
            }

            return $output;
        });
    }
}
