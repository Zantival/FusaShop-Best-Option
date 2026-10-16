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
}
