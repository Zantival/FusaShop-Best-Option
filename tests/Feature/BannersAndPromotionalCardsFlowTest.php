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

class BannersAndPromotionalCardsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchant(string $companyName = 'Vivero Las Orquídeas'): User
    {
        $merchant = User::create([
            'name'              => $companyName,
            'email'             => 'merchant_' . uniqid() . '@fusashop.test',
            'password'          => bcrypt('password'),
            'role'              => 'merchant',
            'email_verified_at' => now(),
            'is_blocked'        => false,
        ]);

        CompanyProfile::create([
            'merchant_id'              => $merchant->id,
            'company_name'             => $companyName,
            'nit'                      => '900123456-' . $merchant->id,
            'address'                  => 'Centro, Fusagasugá',
            'phone'                    => '3101234567',
            'rut_path'                 => 'kyc/rut.pdf',
            'chamber_of_commerce_path' => 'kyc/camara.pdf',
            'kyc_status'               => 'approved',
        ]);

        return $merchant;
    }

    private function createAnalyst(): User
    {
        return User::create([
            'name'              => 'Analista FusaShop',
            'email'             => 'analyst_' . uniqid() . '@fusashop.test',
            'password'          => bcrypt('password'),
            'role'              => 'analyst',
            'email_verified_at' => now(),
            'is_blocked'        => false,
        ]);
    }

    public function test_empty_banners_and_empty_promo_cards_hide_sections_on_home(): void
    {
        GlobalBanner::query()->delete();
        Setting::setValue('promotional_cards', json_encode([]), 'marketing');

        $analyst = $this->createAnalyst();
        // Visiting analyst.banners must NOT auto-seed fake promotional cards
        $this->actingAs($analyst)->get(route('analyst.banners'))->assertOk();

        $response = $this->get(route('consumer.home'));
        $response->assertOk();
        $response->assertDontSee('id="top-banners-section"', false);
        $response->assertDontSee('id="bottom-banners-section"', false);
        $response->assertDontSee('id="promo-cards-section"', false);
    }

    public function test_eight_active_banners_distribute_four_top_and_four_bottom(): void
    {
        GlobalBanner::query()->delete();

        // Create 3 active banners -> only top section should appear, bottom section hidden
        for ($i = 1; $i <= 3; $i++) {
            GlobalBanner::create([
                'title'      => "Banner Arriba {$i}",
                'image_path' => "banners/top-{$i}.jpg",
                'link_url'   => "http://localhost/tienda/{$i}",
                'is_active'  => true,
                'sort_order' => $i,
            ]);
        }

        $resThree = $this->get(route('consumer.home'));
        $resThree->assertOk();
        $resThree->assertSee('id="top-banners-section"', false);
        $resThree->assertDontSee('id="bottom-banners-section"', false);
        $resThree->assertSee('Banner Arriba 1');
        $resThree->assertSee('Banner Arriba 3');

        // Add 5 more active banners (total 8) -> 4 top and 4 bottom
        for ($i = 4; $i <= 8; $i++) {
            GlobalBanner::create([
                'title'      => $i <= 4 ? "Banner Arriba {$i}" : "Banner Abajo {$i}",
                'image_path' => "banners/slot-{$i}.jpg",
                'link_url'   => "http://localhost/tienda/{$i}",
                'is_active'  => true,
                'sort_order' => $i,
            ]);
        }

        $resEight = $this->get(route('consumer.home'));
        $resEight->assertOk();
        $resEight->assertSee('id="top-banners-section"', false);
        $resEight->assertSee('id="bottom-banners-section"', false);
        $resEight->assertSee('Banner Arriba 4');
        $resEight->assertSee('Banner Abajo 5');
        $resEight->assertSee('Banner Abajo 8');
    }

    public function test_merchant_banner_request_shows_image_and_store_link_for_analyst_and_publishes_to_store(): void
    {
        Storage::fake('public');
        $merchant = $this->createMerchant('Café Real Sumapaz');
        $analyst = $this->createAnalyst();

        $file = UploadedFile::fake()->create('banner-tienda.jpg', 200, 'image/jpeg');

        $this->actingAs($merchant)->post(route('merchant.banner.request.store'), [
            'image'        => $file,
            'title'        => 'Gran Feria Café Real Sumapaz',
            'description'  => 'Por favor enlazar este banner directamente a nuestra tienda oficial.',
            'target_type'  => 'store',
            'payment_mode' => 'wompi_card',
            'card_holder'  => 'Café Real',
            'card_number'  => '4242424242424242',
            'card_expiry'  => '12/28',
            'card_cvc'     => '123',
        ])->assertRedirect(route('merchant.dashboard'));

        $bannerReq = BannerRequest::first();
        $this->assertNotNull($bannerReq);
        $expectedStoreUrl = route('consumer.merchant.profile', $merchant->id);
        $this->assertSame($expectedStoreUrl, $bannerReq->target_url);

        // Analyst sees the request message, photo, and store link in analyst.banners
        $analystPage = $this->actingAs($analyst)->get(route('analyst.banners'));
        $analystPage->assertOk();
        $analystPage->assertSee('Gran Feria Café Real Sumapaz');
        $analystPage->assertSee('Por favor enlazar este banner directamente a nuestra tienda oficial.');
        $analystPage->assertSee($expectedStoreUrl, false);

        // Analyst approves the banner request
        $this->actingAs($analyst)->post(route('analyst.banner-requests.approve', $bannerReq->id), [
            'publish_as'           => 'banner',
            'selected_target_url'  => $expectedStoreUrl,
        ])->assertRedirect(route('analyst.banners', ['tab' => 'banners']));

        $globalBanner = GlobalBanner::where('is_active', true)->first();
        $this->assertNotNull($globalBanner);
        $this->assertSame($expectedStoreUrl, $globalBanner->link_url);

        // Consumer sees the active banner on home linking directly to the merchant store
        $homePage = $this->get(route('consumer.home'));
        $homePage->assertOk();
        $homePage->assertSee('id="top-banners-section"', false);
        $homePage->assertSee('Gran Feria Café Real Sumapaz');
        $homePage->assertSee($expectedStoreUrl, false);
    }

    public function test_merchant_can_pay_unique_promo_card_for_discounted_products_and_analyst_links_them(): void
    {
        Storage::fake('public');
        Setting::setValue('promotional_cards', json_encode([]), 'marketing');

        $merchant = $this->createMerchant('Artesanías Chinauta');
        $analyst = $this->createAnalyst();

        $prod1 = Product::create([
            'merchant_id'      => $merchant->id,
            'name'             => 'Sombrero Artesanal Sumapaz',
            'slug'             => 'sombrero-artesanal-sumapaz',
            'description'      => 'Tejido a mano',
            'category'         => 'Artesanías',
            'price'            => 100000,
            'discount_percent' => 25,
            'allows_points'    => true,
            'stock'            => 10,
            'is_active'        => true,
        ]);

        $prod2 = Product::create([
            'merchant_id'      => $merchant->id,
            'name'             => 'Mochila Tradicional Fusa',
            'slug'             => 'mochila-tradicional-fusa',
            'description'      => 'Algodón orgánico',
            'category'         => 'Artesanías',
            'price'            => 80000,
            'discount_percent' => 20,
            'allows_points'    => true,
            'stock'            => 15,
            'is_active'        => true,
        ]);

        $photo = UploadedFile::fake()->create('productos-promo.jpg', 200, 'image/jpeg');

        // Merchant pays and requests a Unique Promotional Card for their discounted products
        $this->actingAs($merchant)->post(route('merchant.banner.request.store'), [
            'image'        => $photo,
            'title'        => 'Especial Artesanías Chinauta en Descuento',
            'description'  => 'Vincular nuestro sombrero (-25%) y mochila (-20%) a esta tarjeta promocional única.',
            'target_type'  => 'promo_card',
            'product_ids'  => [$prod1->id, $prod2->id],
            'payment_mode' => 'wompi_card',
            'card_holder'  => 'Artesanías Chinauta',
            'card_number'  => '4242424242424242',
            'card_expiry'  => '12/28',
            'card_cvc'     => '123',
        ])->assertRedirect(route('merchant.dashboard'));

        $promoReq = BannerRequest::latest()->first();
        $this->assertNotNull($promoReq);
        $this->assertSame('promo_card', $promoReq->target_type);
        $this->assertCount(2, $promoReq->selected_products);

        $expectedProductsCatalogUrl = route('consumer.catalog', ['products' => "{$prod1->id},{$prod2->id}"]);
        $this->assertSame($expectedProductsCatalogUrl, $promoReq->target_url);

        // Analyst sees the request and discounted products in analyst.banners (Promos tab) and detail view
        $detailPage = $this->actingAs($analyst)->get(route('analyst.banner-requests.show', $promoReq->id));
        $detailPage->assertOk();
        $detailPage->assertSee('Tarjeta Promocional Única');
        $detailPage->assertSee('Sombrero Artesanal Sumapaz');
        $detailPage->assertSee('-25% OFF');
        $detailPage->assertSee('Mochila Tradicional Fusa');
        $detailPage->assertSee('-20% OFF');

        // Analyst approves and links the products to publish the Promotional Card
        $this->actingAs($analyst)->post(route('analyst.banner-requests.approve', $promoReq->id), [
            'publish_as'   => 'promo_card',
            'product_ids'  => [$prod1->id, $prod2->id],
            'banner_title' => 'Especial Artesanías Chinauta en Descuento',
        ])->assertRedirect(route('analyst.banners', ['tab' => 'promos']));

        // Consumer sees the Promotional Card on home with product photo, discounted products, and direct link
        $homePage = $this->get(route('consumer.home'));
        $homePage->assertOk();
        $homePage->assertSee('id="promo-cards-section"', false);
        $homePage->assertSee('Especial Artesanías Chinauta en Descuento');
        $homePage->assertSee('-25% OFF');
        $homePage->assertSee('Sombrero Artesanal Sumapaz');
        $homePage->assertSee($expectedProductsCatalogUrl, false);

        // Clicking that link opens the catalog filtered to those exact discounted products showing real & discounted price
        $catalogPage = $this->get($expectedProductsCatalogUrl);
        $catalogPage->assertOk();
        $catalogPage->assertSee('Sombrero Artesanal Sumapaz');
        $catalogPage->assertSee('$100.000'); // Real price
        $catalogPage->assertSee('$75.000');  // Discounted price
        $catalogPage->assertSee('Mochila Tradicional Fusa');
        $catalogPage->assertSee('$80.000');  // Real price
        $catalogPage->assertSee('$64.000');  // Discounted price
    }
}
