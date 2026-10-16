<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendLowStockAlert;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Reservas de inventario con bloqueo pesimista.
 * reserve() al crear el pedido -> commit() al aprobarse el pago -> release() si se rechaza/expira.
 */
class StockService
{
    /** @throws InsufficientStockException */
    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                if ($product->availableStock() < $item->quantity) {
                    throw new InsufficientStockException("Stock insuficiente para \"{$product->name}\" (disponible: {$product->availableStock()}).");
                }
                $product->increment('reserved_stock', $item->quantity);
            }
            $order->forceFill(['stock_reserved' => true])->save();
        });
    }

    /** Pago aprobado: la reserva se convierte en descuento real de stock. */
    public function commit(Order $order): void
    {
        if (!$order->stock_reserved) {
            return;
        }

        foreach ($order->items as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
            if (!$product) {
                continue;
            }
            $product->stock = max(0, $product->stock - $item->quantity);
            $product->reserved_stock = max(0, $product->reserved_stock - $item->quantity);
            $product->save();

            if ($product->stock <= 0) {
                event(new \App\Events\ProductOutOfStock($product));
            }
            if ($product->isLowStock()) {
                SendLowStockAlert::dispatch($product->id)->afterCommit();
            }
        }
        $order->forceFill(['stock_reserved' => false])->save();
    }

    /** Pago rechazado / error / reserva vencida: libera lo reservado. */
    public function release(Order $order): void
    {
        if (!$order->stock_reserved) {
            return;
        }

        foreach ($order->items as $item) {
            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
            if ($product) {
                $product->reserved_stock = max(0, $product->reserved_stock - $item->quantity);
                $product->save();
            }
        }
        $order->forceFill(['stock_reserved' => false])->save();
    }
}
