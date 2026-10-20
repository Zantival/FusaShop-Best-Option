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
    public const POINT_VALUE = 100;          // 1 FusaCoin = $100 COP de descuento
    public const EARN_RATE_AMOUNT = 10000;    // 1 FusaCoin ganado por cada $10.000 COP de compra
    public const MAX_POINTS_DISCOUNT = 50000; // Máximo $50.000 COP de descuento por compra (500 FusaCoins)

    public function __construct(private StockService $stock)
    {
    }

    /**
     * Crea el pedido PENDIENTE con reserva de stock. El pago se confirma únicamente por webhook.
     *
     * @param array{carrier:string,service:string,price:float} $rate
     * @param int[]|null $selectedItemIds
     */
    public function createOrder(User $user, Cart $cart, ShippingAddress $address, array $rate, string $docType, string $doc, bool $usePoints, ?array $selectedItemIds = null): Order
    {
        return DB::transaction(function () use ($user, $cart, $address, $rate, $docType, $doc, $usePoints, $selectedItemIds) {
            $cart->load('items.product');

            // Filtrar solo los ítems seleccionados para comprar
            $items = $cart->items;
            if (!empty($selectedItemIds)) {
                $items = $items->whereIn('id', $selectedItemIds);
                abort_if($items->isEmpty(), 422, 'Debes seleccionar al menos un producto para comprar.');
            }

            $subtotal = $items->sum(fn ($i) => $i->quantity * $i->product->effectivePrice());
            $shipping = (float) $rate['price'];

            $discount = 0;
            if ($usePoints) {
                // Solo productos donde el comerciante permite redimir puntos (allows_points = true)
                $eligibleSubtotal = $items->filter(fn ($i) => (bool) ($i->product->allows_points ?? true))
                    ->sum(fn ($i) => $i->quantity * $i->product->effectivePrice());

                $merchantIds = $items->pluck('product.merchant_id')->unique()->all();
                $discount = $this->consumePoints($user->id, $merchantIds, $eligibleSubtotal);
            }

            $snapshot = $address->toSnapshot();
            $snapshot['email'] = $user->email;

            // FusaCoins que ganará el usuario con esta compra (1 por cada $10.000 COP)
            $pointsEarned = (int) floor($subtotal / self::EARN_RATE_AMOUNT);

            $order = Order::create([
                'user_id' => $user->id,
                'reference' => 'FS-' . strtoupper(Str::ulid()->toBase32()),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'points_used' => (int) ($discount / self::POINT_VALUE),
                'points_earned' => $pointsEarned,
                'shipping_cost' => $shipping,
                'total' => max(0, $subtotal - $discount) + $shipping,
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

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->effectivePrice(),
                    'selected_options' => $item->selected_options,
                ]);
            }
            $order->load('items.product');

            $this->stock->reserve($order); // lanza InsufficientStockException -> rollback completo

            // El carrito NO se vacía aquí: si el pago es rechazado o abandonado, los productos
            // permanecen en el carrito. Se retiran únicamente al aprobarse el pago
            // (OrderPaymentService::approve -> clearPurchasedItemsFromCart).

            return $order;
        });
    }

    /** Descuenta puntos de fidelidad (1 punto = $100 COP, máx $50.000 COP por compra) y devuelve el valor del descuento aplicado. */
    public function consumePoints(int $userId, array $merchantIds, float $eligibleSubtotal): float
    {
        if ($eligibleSubtotal <= 0) {
            return 0;
        }

        $records = LoyaltyPoint::where('user_id', $userId)->whereIn('merchant_id', $merchantIds)
            ->where('points', '>', 0)->lockForUpdate()->get();

        $available = $records->sum('points');
        if ($available <= 0) {
            return 0;
        }

        // Regla: 1 punto = $100 COP, tope de $50.000 COP por compra, y no más que el subtotal elegible
        $maxPossibleDiscount = min($available * self::POINT_VALUE, $eligibleSubtotal, self::MAX_POINTS_DISCOUNT);
        $discount = max(0, $maxPossibleDiscount);
        $remaining = $discount;

        foreach ($records as $lr) {
            if ($remaining <= 0) break;
            $value = $lr->points * self::POINT_VALUE;
            if ($value <= $remaining) {
                $remaining -= $value;
                $lr->update(['points' => 0]);
            } else {
                $pointsToDeduct = (int) ceil($remaining / self::POINT_VALUE);
                $lr->decrement('points', $pointsToDeduct);
                $remaining = 0;
            }
        }

        return $discount;
    }
}
