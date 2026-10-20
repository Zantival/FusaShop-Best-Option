<?php

namespace Tests\Feature;

use App\Models\BannerRequest;
use App\Models\CompanyProfile;
use App\Models\GlobalBanner;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchantBannerAdvertisingTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private User $analyst;
    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->merchant = User::create([
            'name' => 'Don Pedro Viveros',
            'email' => 'pedro@viveros.com',
            'password' => 'secret123',
            'role' => 'merchant',
            'phone' => '3101234567',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'user_id' => $this->merchant->id,
            'merchant_id' => $this->merchant->id,
            'company_name' => 'Vivero Sumapaz Verde',
            'phone' => '3101234567',
            'address' => 'Fusagasugá Centro',
            'rut_path' => 'ruts/test.pdf',
            'kyc_status' => 'approved',
            'description' => 'Plantas y orquídeas de Fusagasugá',
            'status' => 'approved',
        ]);

        $this->analyst = User::create([
            'name' => 'Analista Admin',
            'email' => 'admin@fusashop.com',
            'password' => 'secret123',
            'role' => 'analyst',
            'phone' => '3209876543',
            'email_verified_at' => now(),
        ]);

        $this->product1 = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Orquídea Cattleya Fusa',
            'slug' => 'orquidea-cattleya-fusa',
            'price' => 45000,
            'stock' => 15,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Bonsái Guayacán Real',
            'slug' => 'bonsai-guayacan-real',
            'price' => 90000,
            'stock' => 8,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_banner_request_form_with_required_sections(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.banner.request'));

        $response->assertOk();
        // Paso 1: Imagen primero
        $response->assertSee('Imagen o Video de la Publicación');
        // Paso 2: Descripción para el admin
        $response->assertSee('Descripción e Instrucciones para el Administrador');
        // Paso 3: Destino (Tienda o productos en oferta)
        $response->assertSee('Modalidad de Pauta:');
        $response->assertSee('Banner Directo a mi Tienda');
        $response->assertSee('Tarjeta Promocional Única');
        $response->assertSee('Orquídea Cattleya Fusa');
        $response->assertSee('Bonsái Guayacán Real');
        // Paso 4: Pago seguro
        $response->assertSee('Resumen de Inversión');
        $response->assertSee('Pago en Línea Seguro');
        $response->assertSee('Número de Tarjeta');
    }

    public function test_merchant_can_submit_banner_request_targeting_store(): void
    {
        $image = UploadedFile::fake()->create('banner_vivero.png', 500, 'image/png');

        $response = $this->actingAs($this->merchant)->post(route('merchant.banner.request.store'), [
            'image' => $image,
            'title' => 'Festival Floral de Fusagasugá',
            'description' => 'Favor publicar con aviso de envíos gratis en la cabecera municipal.',
            'target_type' => 'store',
            'card_holder' => 'Pedro Perez',
            'card_number' => '4000 1234 5678 9010',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
        ]);

        $response->assertRedirect(route('merchant.dashboard'));
        $this->assertDatabaseHas('banner_requests', [
            'user_id' => $this->merchant->id,
            'title' => 'Festival Floral de Fusagasugá',
            'target_type' => 'store',
            'notes' => 'Favor publicar con aviso de envíos gratis en la cabecera municipal.',
            'status' => 'pending',
        ]);

        $bannerRequest = BannerRequest::where('user_id', $this->merchant->id)->first();
        $this->assertNotNull($bannerRequest);
        $this->assertSame(route('consumer.merchant.profile', $this->merchant->id), $bannerRequest->target_url);
        Storage::disk('public')->assertExists($bannerRequest->image_path);
    }

    public function test_merchant_can_submit_banner_request_targeting_offer_products(): void
    {
        $image = UploadedFile::fake()->create('banner_orquideas.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($this->merchant)->post(route('merchant.banner.request.store'), [
            'image' => $image,
            'title' => 'Super Oferta Orquídeas 30% OFF',
            'description' => 'Vincular a las orquídeas en promoción.',
            'target_type' => 'products',
            'product_ids' => [$this->product1->id, $this->product2->id],
            'card_holder' => 'Pedro Perez',
            'card_number' => '4000 1234 5678 9010',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
        ]);

        $response->assertRedirect(route('merchant.dashboard'));

        $bannerRequest = BannerRequest::where('user_id', $this->merchant->id)->first();
        $this->assertNotNull($bannerRequest);
        $this->assertSame('products', $bannerRequest->target_type);
        $this->assertCount(2, $bannerRequest->selected_products);
        $this->assertSame($this->product1->name, $bannerRequest->selected_products[0]['name']);
        $this->assertNotEmpty($bannerRequest->target_url);
    }

    public function test_analyst_can_view_request_and_approve_it_creating_global_banner(): void
    {
        $bannerRequest = BannerRequest::create([
            'user_id' => $this->merchant->id,
            'title' => 'Promoción Exclusiva de Temporada',
            'image_path' => 'banners/requests/fake_banner.jpg',
            'payment_proof_path' => 'paid_online_12345',
            'status' => 'pending',
            'cost' => 150000,
            'notes' => 'Publicar en carrusel principal.',
            'target_type' => 'products',
            'selected_products' => [
                ['id' => $this->product1->id, 'name' => $this->product1->name, 'price' => 45000, 'url' => route('consumer.product', $this->product1->id)],
            ],
            'target_url' => route('consumer.product', $this->product1->id),
        ]);

        $viewResponse = $this->actingAs($this->analyst)->get(route('analyst.banner-requests.show', $bannerRequest->id));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Promoción Exclusiva de Temporada');
        $viewResponse->assertSee('Publicar en carrusel principal.');
        $viewResponse->assertSee('Orquídea Cattleya Fusa');

        // El analista aprueba la pauta con 1 solo clic utilizando la ruta preconfigurada
        $approveResponse = $this->actingAs($this->analyst)->post(route('analyst.banner-requests.approve', $bannerRequest->id), [
            'link_type' => 'product',
            'selected_target_url' => route('consumer.product', $this->product1->id),
        ]);

        $approveResponse->assertRedirect(route('analyst.banners', ['tab' => 'banners']));
        $this->assertSame('approved', $bannerRequest->fresh()->status);

        $this->assertDatabaseHas('global_banners', [
            'image_path' => 'banners/requests/fake_banner.jpg',
            'link_url' => route('consumer.product', $this->product1->id),
            'is_active' => true,
        ]);
    }
}
