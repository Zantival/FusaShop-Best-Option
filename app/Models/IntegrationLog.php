<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntegrationLog extends Model
{
    protected $fillable = [
        'service', 'action', 'order_id', 'status', 'http_status', 'request',
        'response', 'error', 'retry_job', 'retries', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['request' => 'array', 'response' => 'array', 'resolved_at' => 'datetime'];
    }

    public function order() { return $this->belongsTo(Order::class); }

    /** Claves que nunca deben persistirse en logs. */
    private const SENSITIVE = ['password', 'client_secret', 'access_token', 'authorization', 'token', 'private_key'];

    public static function success(string $service, string $action, ?int $orderId, array $request = [], $response = null, ?int $http = null): self
    {
        return self::create([
            'service' => $service, 'action' => $action, 'order_id' => $orderId,
            'status' => 'success', 'http_status' => $http,
            'request' => self::scrub($request), 'response' => self::scrub(self::normalizeResponse($response)),
        ]);
    }

    public static function failure(string $service, string $action, ?int $orderId, Throwable|string $error, array $request = [], $response = null, ?int $http = null, ?string $retryJob = null): self
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;
        Log::error("[{$service}] {$action} falló: {$message}", ['order_id' => $orderId]);

        return self::create([
            'service' => $service, 'action' => $action, 'order_id' => $orderId,
            'status' => 'failed', 'http_status' => $http, 'error' => mb_substr($message, 0, 2000),
            'request' => self::scrub($request), 'response' => self::scrub(self::normalizeResponse($response)),
            'retry_job' => $retryJob,
        ]);
    }

    private static function normalizeResponse($v): array
    {
        if (is_array($v)) return $v;
        if ($v === null) return [];
        return ['raw' => mb_substr((string) $v, 0, 4000)];
    }

    private static function scrub(array $data): array
    {
        foreach ($data as $k => $v) {
            if (in_array(strtolower((string) $k), self::SENSITIVE, true)) {
                $data[$k] = '***';
            } elseif (is_array($v)) {
                $data[$k] = self::scrub($v);
            } elseif (is_string($v) && strlen($v) > 2000) {
                $data[$k] = '[' . strlen($v) . ' bytes]'; // PDFs/XML base64
            }
        }
        return $data;
    }
}
