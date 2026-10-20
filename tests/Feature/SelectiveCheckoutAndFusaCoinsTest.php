<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderPaymentService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectiveCheckoutAndFusaCoinsTest extends TestCase
{
    use RefreshDatabase;

    private User $consumer;
    private User $merchant;
    private Product $productEligible;
    private Product $productIneligible;
    private ShippingAddress $address;
    private Cart $cart;
    private CartItem $item1;
    private CartItem $item2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.envia.token' => '', 'services.factus.client_id' => '']);

        $this->merchant = User::create([
            'name' => 'Merchant Test',
            'email' => 'merchant@test.com',
            'password' => bcrypt('password123'),
            'role' => 'merchant',
            'email_verified_at' => now(),
        ]);
        $this->consumer = User::create([
            'name' => 'Consumer Test',
            'email' => 'consumer@test.com',
            'password' => bcrypt('password123'),
            'role' => 'consumer',
            'email_verified_at' => now(),
        ]);

        // Producto 1: Permite FusaCoins ($40.000 COP)
        $this->productEligible = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Camisa Artesanal',
            'slug' => 'camisa-artesanal',
            'price' => 40000,
            'stock' => 10,
            'category' => 'Ropa',
            'is_active' => true,
            'allows_points' => true,
        ]);

        // Producto 2: NO permite FusaCoins ($30.000 COP)
        $this->productIneligible = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Sombrero Exclusivo',
            'slug' => 'sombrero-exclusivo',
            'price' => 30000,
            'stock' => 10,
            'category' => 'Accesorios',
            'is_active' => true,
            'allows_points' => false,
        ]);

        $this->address = ShippingAddress::create([
            'user_id' => $this->consumer->id,
            'label' => 'Casa',
            'recipient' => 'Juan PÃ©rez',
            'phone' => '3001234567',
            'street' => 'Calle 8 # 5-20',
            'city' => 'FusagasugÃ¡',
            'state' => 'Cundinamarca',
            'postal_code' => '252211',
            'is_default' => true,
        ]);

        $this->cart = Cart::create(['user_id' => $this->consumer->id]);
        $this->item1 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->productEligible->id,
            'quantity' => 1,
        ]);
        $this->item2 = CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->productIneligible->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_displays_fusacoins_and_badges(): void
    {
        LoyaltyPoint::create([
            'user_id' => $this->consumer->id,
            'merchant_id' => $this->merchant->id,
            'points' => 200,
        ]);

        $response = $this->actingAs($this->consumer)->get(route('consumer.cart'));

        $response->assertOk();
        $response->assertSee('200');
        $response->assertSee('Aplica FusaCoins');
        $response->assertSee('No redime FusaCoins');
        $response->assertSee('1 pt = $100 COP');
    }

    public function test_selective_checkout_only_orders_selected_items_and_leaves_unselected_in_cart(): void
    {
        $checkout = app(CheckoutService::class);
        $rate = ['carrier' => 'servientrega', 'service' => 'ground', 'price' => 10000];

        // Seleccionamos SOLO item1 (Camisa, $40.000)
        $order = $checkout->createOrder(
            $this->consumer,
            $this->cart,
            $this->address,
            $rate,
            'CC',
            '1020304050',
            false,
            [$this->item1->id]
        );

        $this->assertSame(40000.0, (float) $order->subtotal);
        $this->assertSame(10000.0, (float) $order->shipping_cost);
        $this->assertSame(50000.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame($this->productEligible->id, $order->items->first()->product_id);

        // Con el pedido pendiente el carrito permanece intacto (si el pago falla, no se pierde nada)
        $this->assertDatabaseHas('cart_items', ['id' => $this->item1->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $this->item2->id]);

        // Un pago RECHAZADO deja los productos en el carrito
        app(OrderPaymentService::class)->handleTransaction([
            'id' => 'tx-declined-cart',
            'status' => 'DECLINED',
            'reference' => $order->reference,
            'amount_in_cents' => $order->totalInCents(),
            'currency' => 'COP',
        ]);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertDatabaseHas('cart_items', ['id' => $this->item1->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $this->item2->id]);
    }

    public function test_approved_payment_removes_only_purchased_items_from_cart(): void
    {
        $order = app(CheckoutService::class)->createOrder(
            $this->consumer,
            $this->cart,
            $this->address,
            ['carrier' => 'servientrega', 'service' => 'ground', 'price' => 10000],
            'CC',
            '1020304050',
            false,
            [$this->item1->id]
        );

        app(OrderPaymentService::class)->handleTransaction([
            'id' => 'tx-approved-cart',
            'status' => 'APPROVED',
            'reference' => $order->reference,
            'amount_in_cents' => $order->totalInCents(),
            'currency' => 'COP',
        ]);

        $this->assertDatabaseMissing('cart_items', ['id' => $this->item1->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $this->item2->id]);
    }

    public function test_fusacoins_redemption_rules_applied_correctly(): void
    {
        // 500 puntos = $50.000 COP potenciales
        LoyaltyPoint::create([
            'user_id' => $this->consumer->id,
            'merchant_id' => $this->merchant->id,
            'points' => 500,
        ]);

        $checkout = app(CheckoutService::class);
        $rate = ['carrier' => 'servientrega', 'service' => 'ground', 'price' => 8000];

        // Compramos ambos: Subtotal = 70.000 (Eligible = 40.000, Ineligible = 30.000)
        // Aunque el usuario tiene 500 puntos ($50.000), solo puede redimir sobre el subtotal elegible ($40.000).
        $order = $checkout->createOrder(
            $this->consumer,
            $this->cart,
            $this->address,
            $rate,
            'CC',
            '1020304050',
            true, // use_points
            [$this->item1->id, $this->item2->id]
        );

        $this->assertSame(70000.0, (float) $order->subtotal);
        $this->assertSame(40000.0, (float) $order->discount, 'Descuento no puede superar los $40.000 elegibles');
        $this->assertSame(400, (int) $order->points_used, 'DebiÃ³ usar 400 puntos a razÃ³n de $100 COP cada uno');
        $this->assertSame(38000.0, (float) $order->total, '(70.000 - 40.000) + 8.000 de envÃ­o');

        // Puntos restantes del usuario: 500 - 400 = 100 puntos
        $this->assertSame(100, (int) LoyaltyPoint::where('user_id', $this->consumer->id)->sum('points'));
    }

    public function test_fusacoins_earn_rate_and_payment_award(): void
    {
        $checkout = app(CheckoutService::class);
        $rate = ['carrier' => 'servientrega', 'service' => 'ground', 'price' => 10000];

        // Subtotal $40.000 COP -> Gana floor(40000 / 10000) = 4 FusaCoins
        $order = $checkout->createOrder(
            $this->consumer,
            $this->cart,
            $this->address,
            $rate,
            'CC',
            '1020304050',
            false,
            [$this->item1->id]
        );

        $this->assertSame(4, (int) $order->points_earned);

        // Simulamos aprobaciÃ³n de pago en OrderPaymentService
        $paymentService = app(OrderPaymentService::class);
        $tx = [
            'id' => 'tx-test-coins',
            'status' => 'APPROVED',
            'reference' => $order->reference,
            'amount_in_cents' => $order->totalInCents(),
            'currency' => 'COP',
        ];

        $paymentService->handleTransaction($tx);

        $this->assertSame('paid', $order->fresh()->payment_status);
        // Los 4 FusaCoins deben haber sido acreditados al usuario
        $this->assertSame(4, (int) LoyaltyPoint::where('user_id', $this->consumer->id)->where('merchant_id', $this->merchant->id)->value('points'));
    }

    public function test_cart_select_endpoint_updates_session(): void
    {
        $response = $this->actingAs($this->consumer)
            ->postJson(route('consumer.cart.select'), [
                'selected_items' => [$this->item1->id],
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals([$this->item1->id], session('checkout_selected_items'));
    }
}
