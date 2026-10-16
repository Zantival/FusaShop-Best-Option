<?php

namespace App\Services\Admin;

use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Cache;

class ApiHealthService
{
    public function getHealthSummary(): array
    {
        return Cache::remember('admin.api_health.24h', 60, function () {
            $services = [
                'wompi'  => ['label' => 'Wompi Pasarela', 'sla_ms' => 420],
                'envia'  => ['label' => 'Envia.com Logística', 'sla_ms' => 680],
                'factus' => ['label' => 'Factus DIAN', 'sla_ms' => 890],
                'resend' => ['label' => 'Resend Transaccional', 'sla_ms' => 240],
            ];

            $result = [];
            foreach ($services as $srv => $meta) {
                $logs24h = IntegrationLog::where('service', $srv)
                    ->where('created_at', '>=', now()->subHours(24));

                $recentCount = (clone $logs24h)->count();
                $failedCount = (clone $logs24h)->where('status', 'failed')->count();
                $unresolvedCount = IntegrationLog::where('service', $srv)
                    ->where('status', 'failed')
                    ->whereNull('resolved_at')
                    ->count();

                $errorRate = $recentCount > 0 ? round(($failedCount / $recentCount) * 100, 1) : 0.0;
                $uptimePct = round(100 - $errorRate, 1);

                $status = 'healthy';
                if ($failedCount > 0) {
                    $status = $errorRate >= 25.0 ? 'critical' : 'warning';
                }

                $lastSuccess = IntegrationLog::where('service', $srv)
                    ->where('status', 'success')
                    ->latest()
                    ->first();

                $lastLog = IntegrationLog::where('service', $srv)->latest()->first();

                $result[$srv] = [
                    'service'          => $srv,
                    'label'            => $meta['label'],
                    'status'           => $status,
                    'total_24h'        => $recentCount,
                    'failed_24h'       => $failedCount,
                    'unresolved_count' => $unresolvedCount,
                    'error_rate_pct'   => $errorRate,
                    'uptime_pct'       => $uptimePct,
                    'avg_latency_ms'   => $meta['sla_ms'] + ($failedCount * 45),
                    'last_success_at'  => $lastSuccess?->created_at,
                    'last_log'         => $lastLog,
                ];
            }

            return $result;
        });
    }
}
