<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public const POINT_VALUE = 50;

    public function __construct(private StockService $stock)
    {
    }

    /**
     * Crea el pedido PENDIENTE con reserva de stock. El pago se confirma únicamente por webhook.
     *
     * @param array{carrier:string,service:string,price:float} $rate
     */
    public function createOrder(User $user, Cart $cart, ShippingAddress $address, array $rate, string $docType, string $doc, bool $usePoints): Order
    {
        return DB::transaction(function () use ($user, $cart, $address, $rate, $docType, $doc, $usePoints) {
            $cart->load('items.product');
            $subtotal = $cart->items->sum(fn ($i) => $i->quantity * $i->product->price);
            $shipping = (float) $rate['price'];

            $discount = 0;
            if ($usePoints) {
                $discount = $this->consumePoints($user->id, $cart->items->pluck('product.merchant_id')->unique()->all(), $subtotal);
            }

            $snapshot = $address->toSnapshot();
            $snapshot['email'] = $user->email;

            $order = Order::create([
                'user_id' => $user->id,
                'reference' => 'FS-' . strtoupper(Str::ulid()->toBase32()),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'points_used' => (int) ($discount / self::POINT_VALUE),
                'shipping_cost' => $shipping,
                'total' => $subtotal - $discount + $shipping,
                'commission_amount' => round($subtotal * (float) config('services.fusashop.commission_rate'), 2),
                'currency' => 'COP',
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'wompi',
                'shipping_address_id' => $address->id,
                'shipping_address' => strip_tags($address->fullLine()),
                'shipping_snapshot' => $snapshot,
                'shipping_postal_code' => $address->postal_code,
                'shipping_carrier' => $rate['carrier'],
                'shipping_service' => $rate['service'],
                'customer_document_type' => strtoupper($docType),
                'customer_document' => $doc,
                'reservation_expires_at' => now()->addMinutes((int) config('services.fusashop.reservation_minutes')),
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'selected_options' => $item->selected_options,
                ]);
            }
            $order->load('items.product');

            $this->stock->reserve($order); // lanza InsufficientStockException -> rollback completo

            $cart->items()->delete();

            return $order;
        });
    }

    /** Descuenta puntos de fidelidad (1 punto = $50) y devuelve el valor del descuento aplicado. */
    private function consumePoints(int $userId, array $merchantIds, float $subtotal): float
    {
        $records = LoyaltyPoint::where('user_id', $userId)->whereIn('merchant_id', $merchantIds)
            ->where('points', '>', 0)->lockForUpdate()->get();

        $available = $records->sum('points');
        if ($available <= 0) {
            return 0;
        }

        $discount = min($available * self::POINT_VALUE, $subtotal);
        $remaining = $discount;
        foreach ($records as $lr) {
            if ($remaining <= 0) break;
            $value = $lr->points * self::POINT_VALUE;
            if ($value <= $remaining) {
                $remaining -= $value;
                $lr->update(['points' => 0]);
            } else {
                $lr->decrement('points', (int) ceil($remaining / self::POINT_VALUE));
                $remaining = 0;
            }
        }

        return $discount;
    }
}
