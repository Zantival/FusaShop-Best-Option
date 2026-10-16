<?php

namespace App\Jobs\Concerns;

use App\Models\IntegrationLog;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Comportamiento común de jobs de integraciones externas:
 * reintentos con backoff, y registro legible del fallo definitivo (además de failed_jobs).
 */
trait HandlesIntegrationFailure
{
    public int $tries = 5;

    public function backoff(): array
    {
        return [30, 120, 300, 900];
    }

    abstract protected function service(): string;

    abstract protected function action(): string;

    public function failed(Throwable $e): void
    {
        $http = $e instanceof RequestException ? $e->response->status() : null;

        IntegrationLog::failure(
            $this->service(), $this->action(), $this->orderId ?? null, $e,
            [], null, $http, static::class
        );
    }
}
