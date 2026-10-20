<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CompanyProfile;
use App\Models\LoyaltyPoint;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantProductDiscountsAndFusaCoinsTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private User $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::create([
            'name' => 'Café Sumapaz Gourmet',
            'email' => 'cafe@sumapaz.com',
            'password' => 'secret123',
            'role' => 'merchant',
            'phone' => '3101234567',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'user_id' => $this->merchant->id,
            'merchant_id' => $this->merchant->id,
            'company_name' => 'Café Sumapaz Gourmet',
            'phone' => '3101234567',
            'address' => 'Cra 6 # 7-15, Fusagasugá',
            'rut_path' => 'ruts/cafe.pdf',
            'kyc_status' => 'approved',
            'status' => 'approved',
        ]);

        $this->consumer = User::create([
            'name' => 'Laura Compradora',
            'email' => 'laura@cliente.com',
            'password' => 'secret123',
            'role' => 'consumer',
            'phone' => '3209876543',
            'email_verified_at' => now(),
        ]);
    }

    public function test_merchant_can_create_and_update_product_with_discount_percent_and_fusacoins(): void
    {
        // 1. El comerciante crea un producto con 25% de descuento y FusaCoins activados
        $storeRes = $this->actingAs($this->merchant)->post(route('merchant.products.store'), [
            'name' => 'Café Especial Reserva 500g',
            'description' => 'Café de origen cultivado en Fusagasugá',
            'price' => 40000,
            'has_discount' => 1,
            'discount_percent' => 25,
            'stock' => 20,
            'category' => 'Alimentos',
            'is_active' => 1,
            'allows_points' => 1,
        ]);

        $storeRes->assertRedirect(route('merchant.products'));

        $product = Product::where('name', 'Café Especial Reserva 500g')->firstOrFail();
        $this->assertSame(25, $product->discount_percent);
        $this->assertTrue($product->allows_points);
        $this->assertTrue($product->hasDiscount());
        $this->assertEquals(40000.0, $product->originalPrice());
        $this->assertEquals(30000.0, $product->effectivePrice());
        $this->assertEquals(10000.0, $product->discountAmount());

        // 2. En el panel del comerciante se ve el badge -25% OFF, el precio final $30.000 y Aplica FusaCoins
        $merchantList = $this->actingAs($this->merchant)->get(route('merchant.products'));
        $merchantList->assertOk();
        $merchantList->assertSee('-25% OFF');
        $merchantList->assertSee('$30.000');
        $merchantList->assertSee('Aplica FusaCoins');

        // 3. En el catálogo, vitrina del comerciante y detalle del producto, el consumidor ve el precio real tachado y luego el precio con descuento
        $catalogRes = $this->actingAs($this->consumer)->get(route('consumer.catalog'));
        $catalogRes->assertOk();
        $catalogRes->assertSee('Antes:');
        $catalogRes->assertSee('$40.000');
        $catalogRes->assertSee('-25% OFF');
        $catalogRes->assertSee('$30.000');
        $catalogRes->assertSee('¡Ahorras $10.000 COP!');

        $storefrontRes = $this->actingAs($this->consumer)->get(route('consumer.merchant.profile', $this->merchant->id));
        $storefrontRes->assertOk();
        $storefrontRes->assertSee('Antes:');
        $storefrontRes->assertSee('$40.000');
        $storefrontRes->assertSee('$30.000');

        $detailRes = $this->actingAs($this->consumer)->get(route('consumer.product', $product->id));
        $detailRes->assertOk();
        $detailRes->assertSee('Precio real:');
        $detailRes->assertSee('$40.000 COP');
        $detailRes->assertSee('-25% DESCUENTO');
        $detailRes->assertSee('Precio con descuento:');
        $detailRes->assertSee('$30.000');
        $detailRes->assertSee('Ahorras $10.000 COP');
        $detailRes->assertSee('Aplica FusaCoins');

        // 4. El comerciante puede actualizar el descuento o desactivar FusaCoins
        $updateRes = $this->actingAs($this->merchant)->put(route('merchant.products.update', $product->id), [
            'name' => 'Café Especial Reserva 500g',
            'description' => 'Café de origen cultivado en Fusagasugá',
            'price' => 40000,
            'has_discount' => 1,
            'discount_percent' => 10,
            'stock' => 20,
            'category' => 'Alimentos',
            'is_active' => 1,
            'allows_points' => 0,
        ]);
        $updateRes->assertRedirect(route('merchant.products'));

        $product->refresh();
        $this->assertSame(10, $product->discount_percent);
        $this->assertFalse($product->allows_points);
        $this->assertEquals(36000.0, $product->effectivePrice());
    }

    public function test_checkout_applies_both_product_percentage_discount_and_fusacoins(): void
    {
        $product = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Miel Pura del Sumapaz',
            'slug' => 'miel-pura-sumapaz',
            'price' => 100000,
            'discount_percent' => 20, // Precio final efectivo: $80.000
            'stock' => 15,
            'category' => 'Alimentos',
            'is_active' => true,
            'allows_points' => true,
        ]);

        // Darle 50 FusaCoins al comprador ($5.000 COP de descuento)
        LoyaltyPoint::create([
            'user_id' => $this->consumer->id,
            'merchant_id' => $this->merchant->id,
            'points' => 50,
        ]);

        $cart = Cart::create(['user_id' => $this->consumer->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->assertEquals(80000.0, $cart->fresh()->total());

        $address = ShippingAddress::create([
            'user_id' => $this->consumer->id,
            'label' => 'Casa',
            'recipient' => 'Laura Compradora',
            'phone' => '3209876543',
            'street' => 'Calle 10 # 12-34',
            'city' => 'Fusagasuga',
            'state' => 'CU',
            'postal_code' => '252211',
            'country' => 'CO',
            'is_default' => true,
        ]);

        /** @var CheckoutService $checkoutService */
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->createOrder(
            $this->consumer,
            $cart,
            $address,
            ['carrier' => 'envia', 'service' => 'terrestre', 'price' => 8500],
            'CC',
            '1069754321',
            true
        );

        // Subtotal con el 20% de descuento del producto = $80.000
        // Descuento adicional por 50 FusaCoins = $5.000
        // Envío = $8.500 -> Total a pagar = $83.500
        $this->assertEquals(80000.0, (float) $order->subtotal);
        $this->assertEquals(5000.0, (float) $order->discount);
        $this->assertSame(50, (int) $order->points_used);
        $this->assertEquals(83500.0, (float) $order->total);
        $this->assertEquals(80000.0, (float) $order->items->first()->price);
    }
}
