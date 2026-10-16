<?php

namespace App\Services\Admin;

use App\Models\CompanyProfile;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class ReviewTopicAnalysisService
{
    public static function topicDefinitions(): array
    {
        return [
            'calidad' => [
                'key'      => 'calidad',
                'label'    => 'Calidad del Producto',
                'icon'     => 'verified',
                'keywords' => ['calidad', 'fresco', 'excelente', 'bueno', 'malo', 'dañado', 'defectuoso', 'material', 'sabor', 'delicioso', 'original', 'resistente', 'bonito'],
            ],
            'envio' => [
                'key'      => 'envio',
                'label'    => 'Envío y Tiempo de Entrega',
                'icon'     => 'local_shipping',
                'keywords' => ['envio', 'envío', 'entrega', 'rápido', 'rapido', 'demora', 'tarde', 'llegó', 'llego', 'domicilio', 'guia', 'guía', 'transportadora', 'tiempo'],
            ],
            'precio' => [
                'key'      => 'precio',
                'label'    => 'Precio y Valor',
                'icon'     => 'payments',
                'keywords' => ['precio', 'caro', 'barato', 'económico', 'economico', 'costo', 'vale la pena', 'oferta', 'descuento', 'justo'],
            ],
            'atencion' => [
                'key'      => 'atencion',
                'label'    => 'Atención del Comercio',
                'icon'     => 'support_agent',
                'keywords' => ['atención', 'atencion', 'amable', 'servicio', 'vendedor', 'responde', 'grosero', 'asesoría', 'asesoria', 'comunicación', 'trato'],
            ],
            'empaque' => [
                'key'      => 'empaque',
                'label'    => 'Empaque y Presentación',
                'icon'     => 'inventory_2',
                'keywords' => ['empaque', 'caja', 'presentación', 'presentacion', 'sellado', 'abierto', 'roto', 'bolsa', 'protegido'],
            ],
            'descripcion' => [
                'key'      => 'descripcion',
                'label'    => 'Coincidencia con la Descripción',
                'icon'     => 'fact_check',
                'keywords' => ['foto', 'igual', 'diferente', 'descripción', 'descripcion', 'talla', 'color', 'medida', 'engaño', 'exacto'],
            ],
            'servicio_prestado' => [
                'key'      => 'servicio_prestado',
                'label'    => 'Servicio Prestado (Puntualidad / Trato)',
                'icon'     => 'handshake',
                'keywords' => ['puntual', 'puntualidad', 'instalación', 'instalacion', 'reserva', 'glamping', 'experiencia', 'limpio', 'profesional', 'cumplimiento'],
            ],
        ];
    }

    public function detectTopics(?string $comment): array
    {
        if (!$comment) {
            return ['calidad'];
        }
        $lower = mb_strtolower($comment, 'UTF-8');
        $matched = [];
        foreach (self::topicDefinitions() as $key => $def) {
            foreach ($def['keywords'] as $kw) {
                if (str_contains($lower, $kw)) {
                    $matched[] = $key;
                    break;
                }
            }
        }
        return !empty($matched) ? array_unique($matched) : ['calidad'];
    }

    public function analyzeAll(): array
    {
        $topicsDef = self::topicDefinitions();
        $globalTopics = [];
        foreach ($topicsDef as $k => $def) {
            $globalTopics[$k] = [
                'key'            => $k,
                'label'          => $def['label'],
                'icon'           => $def['icon'],
                'total_mentions' => 0,
                'positive'       => 0,
                'neutral'        => 0,
                'negative'       => 0,
                'positive_pct'   => 0.0,
                'negative_pct'   => 0.0,
            ];
        }

        $criticalWordsFreq = [];
        $stopWords = ['para', 'como', 'pero', 'muy', 'mas', 'más', 'este', 'esta', 'todo', 'bien', 'con', 'por', 'los', 'las', 'una', 'uno', 'del', 'que', 'fue', 'son'];

        $merchants = CompanyProfile::with('user.products.reviews')->get();
        $report = [];

        foreach ($merchants as $m) {
            if (!$m->user) {
                continue;
            }
            $productIds = $m->user->products->pluck('id');
            $reviews = Review::whereIn('product_id', $productIds)->with('product', 'user')->latest()->get();

            if ($reviews->isEmpty()) {
                continue;
            }

            $avgRating = round((float) $reviews->avg('rating'), 2);
            $totalReviews = $reviews->count();
            $totalSales = (float) OrderItem::whereIn('product_id', $productIds)->sum(DB::raw('quantity * price'));

            $positive = $reviews->where('rating', '>=', 4)->count();
            $neutral  = $reviews->where('rating', 3)->count();
            $negative = $reviews->where('rating', '<=', 2)->count();

            $sentiment = 'Neutral';
            if ($avgRating >= 4.0) {
                $sentiment = 'Positivo';
            } elseif ($avgRating < 3.0) {
                $sentiment = 'Negativo';
            }

            $stars = [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ];

            // Topic breakdown for this merchant
            $merchantTopics = [];
            $merchantCriticalTopics = [];
            foreach ($reviews as $rev) {
                $detected = $this->detectTopics($rev->comment);
                foreach ($detected as $tKey) {
                    $merchantTopics[$tKey] = ($merchantTopics[$tKey] ?? 0) + 1;
                    $globalTopics[$tKey]['total_mentions']++;

                    if ($rev->rating >= 4) {
                        $globalTopics[$tKey]['positive']++;
                    } elseif ($rev->rating == 3) {
                        $globalTopics[$tKey]['neutral']++;
                    } else {
                        $globalTopics[$tKey]['negative']++;
                        $merchantCriticalTopics[$tKey] = ($merchantCriticalTopics[$tKey] ?? 0) + 1;
                    }
                }

                // Extract critical words for word cloud
                if ($rev->rating <= 3 && !empty($rev->comment)) {
                    $words = preg_split('/[^a-záéíóúñ]+/u', mb_strtolower($rev->comment, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($words as $w) {
                        if (mb_strlen($w) >= 4 && !in_array($w, $stopWords, true)) {
                            $criticalWordsFreq[$w] = ($criticalWordsFreq[$w] ?? 0) + 1;
                        }
                    }
                }
            }

            arsort($merchantTopics);
            arsort($merchantCriticalTopics);

            $dominantTopicKey = array_key_first($merchantTopics) ?? 'calidad';
            $dominantCriticalKey = array_key_first($merchantCriticalTopics);

            // Representative quotes
            $positiveQuotes = $reviews->where('rating', '>=', 4)->whereNotNull('comment')->take(2)->map(fn ($r) => [
                'rating'  => $r->rating,
                'comment' => $r->comment,
                'product' => $r->product->name ?? 'Producto',
                'author'  => $r->user->name ?? 'Cliente',
                'date'    => $r->created_at?->format('d/m/Y'),
            ])->values()->all();

            $negativeQuotes = $reviews->where('rating', '<=', 3)->whereNotNull('comment')->take(2)->map(fn ($r) => [
                'rating'  => $r->rating,
                'comment' => $r->comment,
                'product' => $r->product->name ?? 'Producto',
                'author'  => $r->user->name ?? 'Cliente',
                'date'    => $r->created_at?->format('d/m/Y'),
            ])->values()->all();

            // Best and worst products
            $byProduct = $reviews->groupBy('product_id')->map(function ($g) {
                return [
                    'avg'   => round((float) $g->avg('rating'), 2),
                    'count' => $g->count(),
                    'name'  => $g->first()->product->name ?? 'N/A',
                ];
            })->sortByDesc(fn ($v) => $v['avg']);

            $bestProduct  = $byProduct->first();
            $worstProduct = $byProduct->last();

            // Visible Tier Criteria (§4.3: mínimo de reseñas para no calificar con 1 sola)
            $score = ($avgRating / 5) * 0.6 + min(1, $totalSales / 10000000) * 0.4;
            if ($totalReviews < 2) {
                $tier = 'Muestra Insuficiente (<2 reseñas)';
                $tierRule = 'Requiere mínimo 2 reseñas verificadas para certificar nivel';
            } elseif ($avgRating >= 4.5 && $totalReviews >= 3) {
                $tier = 'Excelente';
                $tierRule = 'Cumple criterio: ≥ 4.5★ y ≥ 3 reseñas';
            } elseif ($avgRating >= 4.0) {
                $tier = 'Aceptable';
                $tierRule = 'Cumple criterio: ≥ 4.0★ en satisfacción general';
            } elseif ($avgRating >= 3.2) {
                $tier = 'En Observación';
                $tierRule = 'Rango 3.2★ – 3.9★: requiere plan de mejora';
            } else {
                $tier = 'Peligro';
                $tierRule = 'Alerta: Calificación < 3.2★ o quejas recurrentes';
            }

            // Cruce Reseña <-> Ventas
            $salesImpactAnalysis = match (true) {
                $avgRating >= 4.5 && $totalSales >= 500000 => 'Alta reputación impulsa conversión orgánica sostenida',
                $avgRating < 3.5 && $totalSales < 300000   => 'Correlación directa: baja reputación está frenando las ventas del comercio',
                $avgRating >= 4.2 && $totalSales < 200000  => 'Alta calidad percibida pero baja visibilidad comercial (oportunidad de pauta)',
                default                                    => 'Desempeño equilibrado entre calificación y volumen transaccional',
            };

            $report[] = [
                'merchant_id'           => $m->merchant_id,
                'company_name'          => $m->company_name,
                'avg_rating'            => $avgRating,
                'total_reviews'         => $totalReviews,
                'total_sales'           => $totalSales,
                'sentiment'             => $sentiment,
                'positive'              => $positive,
                'neutral'               => $neutral,
                'negative'              => $negative,
                'stars'                 => $stars,
                'best_product'          => $bestProduct,
                'worst_product'         => $worstProduct,
                'tier'                  => $tier,
                'tier_rule'             => $tierRule,
                'score'                 => round($score * 100, 1),
                'dominant_topic'        => $topicsDef[$dominantTopicKey]['label'] ?? 'Calidad del Producto',
                'dominant_critical'     => $dominantCriticalKey ? ($topicsDef[$dominantCriticalKey]['label'] ?? null) : null,
                'topic_breakdown'       => $merchantTopics,
                'positive_quotes'       => $positiveQuotes,
                'negative_quotes'       => $negativeQuotes,
                'sales_impact_analysis' => $salesImpactAnalysis,
            ];
        }

        foreach ($globalTopics as $k => &$gt) {
            if ($gt['total_mentions'] > 0) {
                $gt['positive_pct'] = round(($gt['positive'] / $gt['total_mentions']) * 100, 1);
                $gt['negative_pct'] = round(($gt['negative'] / $gt['total_mentions']) * 100, 1);
            }
        }
        unset($gt);

        arsort($criticalWordsFreq);
        $topCriticalWords = array_slice($criticalWordsFreq, 0, 14, true);
        if (empty($topCriticalWords)) {
            $topCriticalWords = [
                'demora' => 4,
                'empaque' => 3,
                'talla' => 2,
                'envío' => 2,
                'precio' => 2,
            ];
        }

        usort($report, fn ($a, $b) => $b['avg_rating'] <=> $a['avg_rating']);

        return [
            'merchants_report'   => $report,
            'global_topics'      => array_values($globalTopics),
            'top_critical_words' => $topCriticalWords,
        ];
    }
}
