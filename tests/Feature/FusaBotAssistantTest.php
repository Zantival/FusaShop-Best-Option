<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FusaBotAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_assistant_returns_matching_products_with_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/camisa.webp', 'image');

        $merchant = User::create([
            'name' => 'Tienda local',
            'email' => 'tienda@example.com',
            'password' => 'password',
            'role' => 'merchant',
        ]);

        Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Camisa artesanal',
            'slug' => 'camisa-artesanal',
            'description' => 'Camisa hecha en Fusagasugá',
            'price' => 45000,
            'stock' => 4,
            'category' => 'Moda',
            'image' => 'products/camisa.webp',
            'is_active' => true,
        ]);

        $this->getJson(route('consumer.assistant.search', ['q' => 'Muéstrame una prenda']))
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Camisa artesanal')
            ->assertJsonPath('products.0.category', 'Moda')
            ->assertJsonPath('products.0.price', '45.000')
            ->assertJsonPath('products.0.image', url('files/products/camisa.webp'));
    }

    public function test_assistant_gives_shopping_selling_and_profile_guidance(): void
    {
        $this->getJson(route('consumer.assistant.search', ['q' => 'Cómo puedo comprar']))
            ->assertOk()
            ->assertJsonPath('links.0.url', route('consumer.catalog'));

        $this->getJson(route('consumer.assistant.search', ['q' => 'Quiero vender en FusaShop']))
            ->assertOk()
            ->assertJsonPath('links.0.url', route('register'));

        $this->getJson(route('consumer.assistant.search', ['q' => 'Cómo cambio mi perfil']))
            ->assertOk()
            ->assertJsonPath('links.0.url', route('login'));
    }

    public function test_consumer_critical_issue_escalates_to_admin_chat_and_pqrs(): void
    {
        $admin = User::create([
            'name' => 'Admin FusaShop',
            'email' => 'admin@fusashop.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $consumer = User::create([
            'name' => 'Cliente Afectado',
            'email' => 'cliente@example.com',
            'password' => 'password',
            'role' => 'consumer',
        ]);

        $response = $this->actingAs($consumer)->getJson(route('consumer.assistant.search', [
            'q' => 'Tengo un cobro duplicado y necesito hablar con un asesor o administrador'
        ]));

        $response->assertOk()
            ->assertJsonPath('escalate', true)
            ->assertJsonPath('links.0.label', '💬 Hablar con el Administrador en Vivo')
            ->assertJsonPath('links.0.url', route('chat.show', $admin->id))
            ->assertJsonPath('links.1.label', '📝 Radicar PQRS Oficial')
            ->assertJsonPath('links.1.url', route('pqrs.create'));
    }

    public function test_consumer_fusacoins_faq_explains_redemption_rules(): void
    {
        $response = $this->getJson(route('consumer.assistant.search', ['q' => 'Cómo funcionan los fusacoins y puntos']));

        $response->assertOk()
            ->assertJsonPath('escalate', false);
        
        $answer = $response->json('answer');
        $this->assertStringContainsString('1 FusaCoin por cada $10.000 COP', $answer);
        $this->assertStringContainsString('100 COP de descuento', $answer);
        $this->assertStringContainsString('50.000 COP de descuento', $answer);
    }

    public function test_merchant_assistant_provides_sales_tips_and_never_suggests_purchase_products(): void
    {
        $merchant = User::create([
            'name' => 'Vendedor Textil',
            'email' => 'vendedor@textil.com',
            'password' => 'password',
            'role' => 'merchant',
        ]);

        $response = $this->actingAs($merchant)->getJson(route('consumer.assistant.search', [
            'q' => '¿Cómo puedo mejorar la presentación de mis fotos y descripciones para vender más?'
        ]));

        $response->assertOk()
            ->assertJsonPath('products', []);

        $answer = $response->json('answer');
        $this->assertStringContainsString('Fotografías profesionales', $answer);
        $this->assertStringContainsString('Títulos y descripciones atractivas', $answer);
        $this->assertStringContainsString('Activa la redención de FusaCoins', $answer);
    }

    public function test_admin_assistant_provides_live_platform_metrics(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'analyst@fusashop.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $response = $this->actingAs($admin)->getJson(route('consumer.assistant.search', [
            'q' => 'Dame el resumen de ventas y pedidos'
        ]));

        $response->assertOk()
            ->assertJsonPath('products', [])
            ->assertSee('Resumen Financiero y de Operaciones')
            ->assertSee('Ventas Pagadas Totales');
    }
}

