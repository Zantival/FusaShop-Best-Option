<?php

namespace App\Listeners;

use App\Events\ProductOutOfStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifySellerOutOfStock implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ProductOutOfStock $event): void
    {
        $product = $event->product;
        Log::info("The product '{$product->name}' (ID: {$product->id}) is out of stock. Seller ID: {$product->merchant_id} notified.");
        
        $merchant = \App\Models\User::find($product->merchant_id);
        if ($merchant) {
            $merchant->notify(new \App\Notifications\OutOfStockNotification($product));
        }
    }
}
