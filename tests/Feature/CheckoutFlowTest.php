<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\IntegrationLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Jobs\IssueFactusInvoice;
use App\Mail\OrderApprovedMail;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Flujo completo: Carrito -> cotización Envia -> Wompi (firma) -> Webhook -> Factus -> Envia guía -> Resend.
 * Todas las APIs externas se simulan con Http::fake (no se llama a sandbox real).
 */
class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $consumer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.public_key' => 'pub_test_x',
            'services.wompi.integrity_secret' => 'integrity_secret',
            'services.wompi.events_secret' => 'events_secret',
            'services.envia.token' => 'envia_token',
            'services.envia.webhook_secret' => 'envia_hook',
            'services.factus.client_id' => 'id',
            'services.factus.numbering_range_id' => 8,
            'services.fusashop.mcp_token' => 'mcp_secret',
        ]);

        Storage::fake('local');
        Mail::fake();

        $merchant = User::create(['name' => 'Tienda', 'email' => 'm@t.co', 'password' => 'secret123', 'role' => 'merchant', 'email_verified_at' => now()]);
        User::create(['name' => 'Admin', 'email' => 'a@t.co', 'password' => 'secret123', 'role' => 'analyst']);
        $this->consumer = User::create(['name' => 'Juan', 'email' => 'j@t.co', 'password' => 'secret123', 'role' => 'consumer', 'email_verified_at' => now()]);

        $this->product = Product::create([
            'merchant_id' => $merchant->id, 'name' => 'Camiseta', 'slug' => 'camiseta-1',
            'price' => 50000, 'stock' => 10, 'is_active' => true, 'low_stock_threshold' => 3,
            'weight_grams' => 300, 'length_cm' => 30, 'width_cm' => 20, 'height_cm' => 5,
        ]);
    }

    private function fakeExternalApis(): void
    {
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/v1/bills/validate' => Http::response(['data' => ['bill' => ['cufe' => 'CUFE123', 'number' => 'SETP990000001']]]),
            '*/v1/bills/download-pdf/*' => Http::response(['data' => ['pdf_base_64_encoded' => base64_encode('%PDF-fake')]]),
            '*/v1/bills/download-xml/*' => Http::response(['data' => ['xml_base_64_encoded' => base64_encode('<xml/>')]]),
            '*/ship/rate/' => Http::response(['meta' => 'rate', 'data' => [['carrier' => 'servientrega', 'service' => 'ground', 'totalPrice' => 12000, 'deliveryEstimate' => '3 dias']]]),
            '*/ship/generate/' => Http::response(['meta' => 'generate', 'data' => [['trackingNumber' => 'TRK123', 'label' => 'https://labels.test/TRK123.pdf', 'carrier' => 'servientrega']]]),
        ]);
    }

    private function makeOrder(int $qty = 2, float $shipping = 12000): Order
    {
        $order = Order::create([
            'user_id' => $this->consumer->id, 'reference' => 'FS-' . strtoupper(substr(md5(uniqid()), 0, 20)),
            'subtotal' => 50000 * $qty, 'shipping_cost' => $shipping, 'total' => 50000 * $qty + $shipping,
            'status' => 'pending', 'payment_status' => 'pending', 'payment_method' => 'wompi', 'currency' => 'COP',
            'shipping_address' => 'Calle 1 # 2-3', 'shipping_carrier' => 'servientrega', 'shipping_service' => 'ground',
            'customer_document_type' => 'CC', 'customer_document' => '123456789',
            'shipping_snapshot' => ['recipient' => 'Juan', 'phone' => '3001112233', 'street' => 'Calle 1', 'number' => '2-3', 'district' => 'Centro', 'city' => 'Bogota', 'state' => 'DC', 'postal_code' => '110111', 'country' => 'CO'],
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'quantity' => $qty, 'price' => 50000]);
        app(StockService::class)->reserve($order->fresh('items.product'));

        return $order->fresh();
    }

    private function wompiEvent(Order $o, string $status, ?int $cents = null, string $txId = 'tx-1', ?int $ts = null, ?string $secret = null): array
    {
        $cents ??= $o->totalInCents();
        $ts ??= time();
        $checksum = hash('sha256', $txId . $status . $cents . $ts . ($secret ?? 'events_secret'));

        return [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => $txId, 'status' => $status, 'reference' => $o->reference, 'amount_in_cents' => $cents, 'currency' => 'COP']],
            'signature' => ['properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'], 'checksum' => $checksum],
            'timestamp' => $ts,
            'sent_at' => now()->toIso8601String(),
        ];
    }

    public function test_integrity_signature_matches_spec(): void
    {
        $sig = app(\App\Services\WompiService::class)->integritySignature('FS-REF', 6200000, 'COP');
        $this->assertSame(hash('sha256', 'FS-REF' . '6200000' . 'COP' . 'integrity_secret'), $sig);
    }

    public function test_webhook_rejects_invalid_signature_and_dispatches_nothing(): void
    {
        $this->fakeExternalApis();
        $order = $this->makeOrder();

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'APPROVED', null, 'tx-1', null, 'wrong_secret'))->assertStatus(401);

        $this->assertSame('pending', $order->fresh()->payment_status);
        Http::assertNothingSent();
    }

    public function test_webhook_rejects_stale_timestamp(): void
    {
        $order = $this->makeOrder();
        $old = time() - (2 * 86400);

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'APPROVED', null, 'tx-1', $old))->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_approved_event_pays_order_commits_stock_and_triggers_factus_envia_and_mail(): void
    {
        $this->fakeExternalApis();
        $order = $this->makeOrder(qty: 8); // stock 10 -> quedan 2 (<= umbral 3)

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'APPROVED'))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('tx-1', $order->wompi_transaction_id);
        $this->assertFalse($order->stock_reserved);

        $p = $this->product->fresh();
        $this->assertSame(2, $p->stock);
        $this->assertSame(0, $p->reserved_stock);

        // Envia
        $this->assertSame('TRK123', $order->tracking_number);
        $this->assertNotNull($order->label_url);
        // Factus
        $this->assertSame('CUFE123', $order->invoice_cufe);
        Storage::disk('local')->assertExists($order->invoice_pdf_path);
        Storage::disk('local')->assertExists($order->invoice_xml_path);
        // Resend (mail con factura) y alerta de stock crítico
        Mail::assertSent(OrderApprovedMail::class, fn ($m) => $m->hasTo('j@t.co'));
        Mail::assertSent(\App\Mail\LowStockAlertMail::class);
    }

    public function test_replayed_event_is_idempotent(): void
    {
        $this->fakeExternalApis();
        $order = $this->makeOrder(qty: 2);
        $event = $this->wompiEvent($order, 'APPROVED');

        $this->postJson('/api/webhooks/wompi', $event)->assertOk();
        $this->postJson('/api/webhooks/wompi', $event)->assertOk();

        $this->assertSame(8, $this->product->fresh()->stock, 'El stock debe descontarse una sola vez');
        $this->assertSame(1, PaymentTransaction::where('transaction_id', 'tx-1')->count());
        $validateCalls = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), '/v1/bills/validate'))->count();
        $generateCalls = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), '/ship/generate/'))->count();
        $this->assertSame(1, $validateCalls, 'Factus debe emitir una sola factura');
        $this->assertSame(1, $generateCalls, 'Envia debe generar una sola guía');
    }

    public function test_declined_event_releases_reservation_and_cancels_order(): void
    {
        $this->fakeExternalApis();
        $order = $this->makeOrder(qty: 3);
        $this->assertSame(3, $this->product->fresh()->reserved_stock);

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'DECLINED'))->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $p = $this->product->fresh();
        $this->assertSame(10, $p->stock);
        $this->assertSame(0, $p->reserved_stock);
        $this->assertNull($order->tracking_number);
        $this->assertCount(1, $this->consumer->fresh()->notifications);
    }

    public function test_amount_mismatch_never_approves_order(): void
    {
        $this->fakeExternalApis();
        $order = $this->makeOrder();

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'APPROVED', 100))->assertOk();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertTrue(IntegrationLog::where('action', 'amount_mismatch')->exists());
    }

    public function test_external_api_failure_does_not_break_payment_and_is_logged(): void
    {
        // Cola real (database): el webhook responde de inmediato y las integraciones corren aparte.
        config(['queue.default' => 'database']);
        $order = $this->makeOrder();

        $this->postJson('/api/webhooks/wompi', $this->wompiEvent($order, 'APPROVED'))->assertOk();
        $this->assertSame(1, DB::table('jobs')->count(), 'El webhook solo encola ProcessWompiEvent');
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->artisan('queue:work', ['--once' => true])->assertExitCode(0);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(2, DB::table('jobs')->count(), 'Quedan encolados: guía Envia + factura Factus');

        // Factus caído: el job falla, queda logueado y el pago sigue confirmado.
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'tok']),
            '*/v1/bills/validate' => Http::response(['message' => 'DIAN down'], 500),
        ]);
        $job = new IssueFactusInvoice($order->id);
        try {
            $job->handle(app(\App\Services\FactusService::class));
            $this->fail('Se esperaba excepción de Factus');
        } catch (\RuntimeException $e) {
            $job->failed($e);
        }

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->invoice_cufe);
        $log = IntegrationLog::where('service', 'factus')->where('status', 'failed')->first();
        $this->assertNotNull($log);
        $this->assertSame(IssueFactusInvoice::class, $log->retry_job);
    }
    public function test_checkout_creates_pending_order_with_reservation_and_redirects_to_wompi(): void
    {
        $this->fakeExternalApis();
        $address = ShippingAddress::create([
            'user_id' => $this->consumer->id, 'label' => 'Casa', 'recipient' => 'Juan', 'phone' => '3001112233',
            'street' => 'Calle 1', 'number' => '2-3', 'district' => 'Centro', 'city' => 'Bogota', 'state' => 'DC',
            'postal_code' => '110111', 'is_default' => true,
        ]);
        $cart = Cart::create(['user_id' => $this->consumer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 2]);

        $this->actingAs($this->consumer)
            ->postJson('/shop/checkout/quote', ['address_id' => $address->id])
            ->assertOk()->assertJsonPath('rates.0.price', 12000);

        $response = $this->actingAs($this->consumer)->post('/shop/checkout', [
            'address_id' => $address->id,
            'shipping_rate' => 'servientrega|ground',
            'document_type' => 'CC',
            'document' => '1020304050',
        ]);

        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('112000.00', (string) $order->total);
        $this->assertTrue($order->stock_reserved);
        $this->assertSame(2, $this->product->fresh()->reserved_stock);
        $this->assertSame(0, $cart->items()->count());

        $expected = hash('sha256', $order->reference . '11200000' . 'COP' . 'integrity_secret');
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://checkout.wompi.co/p/', $location);
        $this->assertStringContainsString('signature%3Aintegrity=' . $expected, $location);
        $this->assertStringContainsString('amount-in-cents=11200000', $location);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $this->fakeExternalApis();
        $address = ShippingAddress::create([
            'user_id' => $this->consumer->id, 'label' => 'Casa', 'recipient' => 'Juan', 'phone' => '3001112233',
            'street' => 'Calle 1', 'city' => 'Bogota', 'state' => 'DC', 'postal_code' => '110111', 'is_default' => true,
        ]);
        $cart = Cart::create(['user_id' => $this->consumer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => 11]);

        $this->actingAs($this->consumer)->post('/shop/checkout', [
            'address_id' => $address->id, 'shipping_rate' => 'servientrega|ground',
            'document_type' => 'CC', 'document' => '1020304050',
        ])->assertRedirect(route('consumer.cart'));

        $this->assertSame(0, Order::count());
        $this->assertSame(0, $this->product->fresh()->reserved_stock);
    }

    public function test_expired_unpaid_orders_release_stock(): void
    {
        $order = $this->makeOrder(qty: 4);
        $order->update(['reservation_expires_at' => now()->subMinute()]);

        $this->assertSame(1, app(\App\Services\OrderPaymentService::class)->expireStaleOrders());
        $this->assertSame(0, $this->product->fresh()->reserved_stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_merchant_cannot_advance_unpaid_order_nor_foreign_order(): void
    {
        $order = $this->makeOrder();
        $merchant = User::where('role', 'merchant')->first();
        $other = User::create(['name' => 'Otro', 'email' => 'o@t.co', 'password' => 'secret123', 'role' => 'merchant', 'email_verified_at' => now()]);

        // Sin KYC el middleware puede redirigir; probamos la lógica directa del controlador.
        $this->withoutMiddleware(\App\Http\Middleware\EnsureKycApproved::class);

        $this->actingAs($other)->patch(route('merchant.orders.update', $order), ['status' => 'shipped'])->assertForbidden();
        $this->actingAs($merchant)->patch(route('merchant.orders.update', $order), ['status' => 'shipped']);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_envia_tracking_webhook_requires_secret_and_updates_order(): void
    {
        $order = $this->makeOrder();
        $order->update(['payment_status' => 'paid', 'status' => 'processing', 'tracking_number' => 'TRK123']);

        $this->postJson('/api/webhooks/envia', ['data' => ['trackingNumber' => 'TRK123', 'status' => 'delivered']])->assertStatus(401);
        $this->assertSame('processing', $order->fresh()->status);

        $this->postJson('/api/webhooks/envia', ['data' => ['trackingNumber' => 'TRK123', 'status' => 'delivered']], ['X-Webhook-Secret' => 'envia_hook'])->assertOk();
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_customer_can_download_own_invoice_only(): void
    {
        $order = $this->makeOrder();
        Storage::disk('local')->put('invoices/x.pdf', '%PDF');
        $order->update(['invoice_pdf_path' => 'invoices/x.pdf', 'invoice_number' => 'SETP1']);
        $stranger = User::create(['name' => 'X', 'email' => 'x@t.co', 'password' => 'secret123', 'role' => 'consumer', 'email_verified_at' => now()]);

        $this->actingAs($this->consumer)->get(route('consumer.orders.invoice', [$order->id, 'pdf']))->assertOk();
        $this->actingAs($stranger)->get(route('consumer.orders.invoice', [$order->id, 'pdf']))->assertNotFound();
    }

    public function test_mcp_requires_token_and_serves_order_lookup(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/api/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertStatus(401);

        $this->postJson('/api/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Authorization' => 'Bearer mcp_secret'])
            ->assertOk()->assertJsonCount(3, 'result.tools');

        $res = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call',
            'params' => ['name' => 'get_order', 'arguments' => ['reference' => $order->reference]],
        ], ['Authorization' => 'Bearer mcp_secret'])->assertOk();
        $this->assertFalse($res->json('result.isError'));
        $this->assertStringContainsString('"found":true', $res->json('result.content.0.text'));

        $bad = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
            'params' => ['name' => 'get_order', 'arguments' => ['reference' => "x' OR 1=1"]],
        ], ['Authorization' => 'Bearer mcp_secret'])->assertOk();
        $this->assertTrue($bad->json('result.isError'));
    }
}