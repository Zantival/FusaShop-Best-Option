<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera reservas de stock de pedidos sin pago
Illuminate\Support\Facades\Schedule::call(fn () => app(App\Services\OrderPaymentService::class)->expireStaleOrders())
    ->everyFiveMinutes()->name('orders:expire-stale')->withoutOverlapping();
