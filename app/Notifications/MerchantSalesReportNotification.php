<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MerchantSalesReportNotification extends Notification
{
    use Queueable;

    public array $reportPayload;

    public function __construct(array $reportPayload)
    {
        $this->reportPayload = $reportPayload;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isCongrats = ($this->reportPayload['report_type'] ?? 'diagnostic') === 'congratulations';
        $companyName = $this->reportPayload['company_name'] ?? $notifiable->name;
        $totalSalesFormatted = '$ ' . number_format((float) ($this->reportPayload['total_sales'] ?? 0), 0, ',', '.') . ' COP';
        $variationPct = $this->reportPayload['variation_pct'] ?? 0;
        $variationSign = $variationPct >= 0 ? "+{$variationPct}%" : "{$variationPct}%";

        $title = $isCongrats
            ? "🏆 Reporte de Ventas ({$totalSalesFormatted} · {$variationSign}): Reconocimiento y Factores de Crecimiento"
            : "📊 Reporte de Ventas ({$totalSalesFormatted} · {$variationSign}): Diagnóstico Causal y Plan de Mejora";

        $summaryReason = $this->reportPayload['summary_reason']
            ?? ($isCongrats
                ? 'Tus ventas aumentaron gracias a tu catálogo activo, disponibilidad de stock y pasarela digital habilitada.'
                : 'Se detectaron factores que explican la variación en tus ventas y acciones recomendadas para impulsar tu facturación.');

        return [
            'type'        => 'merchant_sales_report',
            'report_type' => $this->reportPayload['report_type'] ?? 'diagnostic',
            'icon'        => $isCongrats ? 'workspace_premium' : 'analytics',
            'title'       => $title,
            'message'     => "{$companyName}: {$summaryReason} Haz clic para abrir tu reporte completo de ventas.",
            'url'         => $this->reportPayload['report_url'] ?? route('merchant.sales-report.show', ['user' => $notifiable->id]),
            'report_data' => $this->reportPayload,
        ];
    }
}
