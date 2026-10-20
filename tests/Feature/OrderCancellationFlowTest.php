<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancelledByBuyerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchantAndBuyer(): array
    {
        $merchant = User::create([
            'name' => 'Comerciante Fusa',
            'email' => 'comerciante@fusashop.test',
            'password' => bcrypt('password'),
            'role' => 'merchant',
            'email_verified_at' => now(),
            'is_approved' => true,
        ]);

        CompanyProfile::create([
            'merchant_id' => $merchant->id,
            'company_name' => 'Tienda Fusa Centro',
            'nit' => '900888777-1',
            'rut_path' => 'kyc/rut-fusa.pdf',
            'kyc_status' => 'approved',
            'address' => 'Calle 7 # 6-12',
            'phone' => '3101234567',
        ]);

        $buyer = User::create([
            'name' => 'Comprador Fusa',
            'email' => 'comprador@fusashop.test',
            'password' => bcrypt('password'),
            'role' => 'consumer',
            'email_verified_at' => now(),
            'points' => 50,
        ]);

        return [$merchant, $buyer];
    }

    public function test_buyer_can_cancel_pending_order_restoring_reserved_stock_and_fusacoins_and_merchant_sees_it(): void
    {
        Notification::fake();

        [$merchant, $buyer] = $this->createMerchantAndBuyer();

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Café Orgánico Sumapaz 500g',
            'slug' => 'cafe-organico-sumapaz-500g',
            'description' => 'Café especial cultivado en Fusagasugá',
            'price' => 25000,
            'stock' => 10,
            'reserved_stock' => 2,
            'category' => 'Alimentos',
            'type' => 'product',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'total' => 45000,
            'discount' => 5000,
            'points_earned' => 0,
            'points_used' => 50,
            'status' => 'pending',
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'shipping_address' => 'Cra 6 # 8-15, Fusagasugá',
            'stock_reserved' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 25000,
        ]);

        // 1. El comprador ve el botón "Cancelar Pedido" en Mis Pedidos
        $this->actingAs($buyer)
            ->get(route('consumer.orders'))
            ->assertOk()
            ->assertSee('Cancelar Pedido');

        // 2. El comprador cancela el pedido indicando un motivo
        $this->actingAs($buyer)
            ->from(route('consumer.orders'))
            ->patch(route('consumer.orders.cancel', $order), [
                'reason' => 'Me equivoqué en la dirección de entrega',
            ])
            ->assertRedirect(route('consumer.orders'))
            ->assertSessionHas('success');

        $order->refresh();
        $product->refresh();
        $buyer->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertFalse((bool) $order->stock_reserved);
        $this->assertStringContainsString('Me equivoqué en la dirección de entrega', (string) $order->notes);

        // El stock reservado se liberó (de 2 a 0) y el stock total sigue intacto en 10
        $this->assertSame(10, (int) $product->stock);
        $this->assertSame(0, (int) $product->reserved_stock);

        // Los 50 FusaCoins usados fueron reembolsados al comprador en su billetera LoyaltyPoint
        $this->assertSame(50, (int) $buyer->totalPoints());

        // Se envió la notificación al comerciante
        Notification::assertSentTo($merchant, OrderCancelledByBuyerNotification::class);

        // 3. El comerciante entra a su panel de pedidos y ve el filtro de Cancelados (1) y el aviso detallado
        $this->actingAs($merchant)
            ->get(route('merchant.orders', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee('Cancelado por el Comprador')
            ->assertSee('Este pedido fue cancelado por el comprador')
            ->assertSee('Me equivoqué en la dirección de entrega')
            ->assertSee('Despacho anulado · Pedido cancelado');

        // 4. El comerciante también ve "Pedido cancelado" en su Dashboard
        $this->actingAs($merchant)
            ->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertSee('Pedido cancelado');
    }

    public function test_buyer_cannot_cancel_shipped_order_or_order_with_tracking_number(): void
    {
        [$merchant, $buyer] = $this->createMerchantAndBuyer();

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Miel Pura Fusagasugá',
            'slug' => 'miel-pura-fusagasuga',
            'description' => 'Miel 100% natural',
            'price' => 30000,
            'stock' => 8,
            'reserved_stock' => 0,
            'category' => 'Alimentos',
            'type' => 'product',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'total' => 30000,
            'discount' => 0,
            'points_earned' => 30,
            'points_used' => 0,
            'status' => 'processing',
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'tracking_number' => 'ENVIA-99887766',
            'shipping_carrier' => 'envia',
            'shipping_address' => 'Barrio Balmoral, Fusagasugá',
            'stock_released' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 30000,
        ]);

        // No puede cancelarse porque ya tiene guía emitida
        $this->assertFalse($order->canBeCancelledByBuyer());

        $this->actingAs($buyer)
            ->from(route('consumer.orders'))
            ->patch(route('consumer.orders.cancel', $order))
            ->assertRedirect(route('consumer.orders'))
            ->assertSessionHas('error');

        $this->assertSame('processing', $order->fresh()->status);
    }
}
