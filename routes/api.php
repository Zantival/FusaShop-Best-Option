<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Analyst\AnalystController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\Webhooks\EnviaWebhookController;
use App\Http\Controllers\Webhooks\WompiWebhookController;

Route::post('/login',    [ApiController::class, 'login']);
Route::post('/register', [ApiController::class, 'register']);

Route::get('/products',      [ApiController::class, 'products']);
Route::get('/products/{id}', [ApiController::class, 'productShow']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',           [ApiController::class, 'logout']);
    Route::get('/cart',              [ApiController::class, 'cart']);
    Route::post('/cart',             [ApiController::class, 'cartAdd']);
    Route::delete('/cart/{item}',    [ApiController::class, 'cartRemove']);
    Route::get('/orders',            [ApiController::class, 'orders']);
    Route::get('/analytics',         [AnalystController::class, 'analyticsData']);
});

// Webhooks de terceros (sin CSRF/sesion; autenticados por firma criptografica o secreto compartido)
Route::post('/webhooks/wompi', WompiWebhookController::class)->middleware('throttle:120,1')->name('webhooks.wompi');
Route::post('/webhooks/envia', EnviaWebhookController::class)->middleware('throttle:120,1')->name('webhooks.envia');

// Servidor MCP (JSON-RPC 2.0, solo lectura)
Route::post('/mcp', McpController::class)->middleware('throttle:60,1')->name('mcp');
