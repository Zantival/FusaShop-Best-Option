<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_phone_number(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $response = $this->post(route('register.post'), [
            'name'                  => 'Carlos Ruiz',
            'email'                 => 'carlos@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'consumer',
            // Missing phone
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', ['email' => 'carlos@example.com']);
    }

    public function test_consumer_registration_succeeds_with_phone(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $response = $this->post(route('register.post'), [
            'name'                  => 'Maria Gomez',
            'email'                 => 'maria@example.com',
            'phone'                 => '3101234567',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'consumer',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'phone' => '3101234567',
            'role'  => 'consumer',
        ]);
    }

    public function test_merchant_registration_succeeds_with_phone(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $response = $this->post(route('register.post'), [
            'name'                  => 'Don Pedro',
            'email'                 => 'pedro@example.com',
            'phone'                 => '3209876543',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'merchant',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'pedro@example.com',
            'phone' => '3209876543',
            'role'  => 'merchant',
        ]);
    }

    public function test_user_can_update_profile_avatar_phone_and_address(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name'              => 'Juan Original',
            'email'             => 'juan@example.com',
            'phone'             => '3001112233',
            'address'           => 'Calle 1 # 2-3',
            'role'              => 'consumer',
            'password'          => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $avatarFile = UploadedFile::fake()->create('my_avatar.png', 50, 'image/png');

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name'    => 'Juan Actualizado',
            'email'   => 'juan@example.com',
            'phone'   => '3157778899',
            'address' => 'Carrera 6 # 10-20, Fusagasugá',
            'avatar'  => $avatarFile,
        ]);

        $response->assertRedirect(route('account.profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Juan Actualizado', $user->name);
        $this->assertSame('3157778899', $user->phone);
        $this->assertSame('Carrera 6 # 10-20, Fusagasugá', $user->address);
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar));
        $this->assertStringContainsString('storage/', $user->avatarUrl());
    }

    public function test_user_can_view_edit_profile_dashboard(): void
    {
        $user = User::create([
            'name'              => 'Carlos Test',
            'email'             => 'carlos@example.com',
            'role'              => 'consumer',
            'phone'             => '3112223344',
            'password'          => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('account.profile.edit'));
        $response->assertOk();
        $response->assertSee('Editar Mi Perfil');
        $response->assertSee('Carlos Test');
        $response->assertSee('3112223344');
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('public');
        $fakePath = 'avatars/test_old.png';
        Storage::disk('public')->put($fakePath, 'dummy');

        $user = User::create([
            'name'              => 'Ana Lopez',
            'email'             => 'ana@example.com',
            'role'              => 'consumer',
            'avatar'            => $fakePath,
            'phone'             => '3000000000',
            'password'          => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => '3000000000',
            'remove_avatar' => 1,
        ]);

        $response->assertRedirect(route('account.profile.edit'));
        $user->refresh();
        $this->assertNull($user->avatar);
        $this->assertNull($user->avatarUrl());
    }

    public function test_merchant_dashboard_renders_with_merchant_sidebar(): void
    {
        $merchant = User::create([
            'name'              => 'Comerciante Sumapaz',
            'email'             => 'comerciante@example.com',
            'role'              => 'merchant',
            'phone'             => '3201112233',
            'password'          => Hash::make('Password123!'),
            'email_verified_at' => now(),
        ]);

        \App\Models\CompanyProfile::create([
            'merchant_id'  => $merchant->id,
            'company_name' => 'Vivero El Paraíso',
            'kyc_status'   => 'approved',
            'phone'        => '3201112233',
            'rut_path'     => 'ruts/test.pdf',
        ]);

        $response = $this->actingAs($merchant)->get(route('merchant.dashboard'));
        $response->assertOk();
        // Verifica que la barra lateral vertical del comerciante está presente
        $response->assertSee('Barra lateral del comerciante');
        $response->assertSee('Vivero El Paraíso');
        $response->assertSee('Módulos Comerciales');
        $response->assertSee('Inventario / Stock');
        $response->assertSee('Pedidos');
        $response->assertSee('Envíos');
        $response->assertSee('Finanzas');
        $response->assertSee('Ver Mi Vitrina');
        // Verifica que la barra del consumidor con categorías no se muestra en el panel del comerciante
        $response->assertDontSee('id="categories-more-toggle"', false);
    }

    public function test_consumer_and_merchant_envia_tracking_experience()
    {
        $merchant = User::create([
            'name'     => 'Vivero Test',
            'email'    => 'vivero_' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'merchant',
            'phone'    => '3201112233',
            'email_verified_at' => now(),
        ]);
        \App\Models\CompanyProfile::create([
            'merchant_id'  => $merchant->id,
            'user_id'      => $merchant->id,
            'company_name' => 'Vivero El Paraíso',
            'kyc_status'   => 'approved',
            'phone'        => '3201112233',
            'rut_path'     => 'ruts/test.pdf',
        ]);
        $product = \App\Models\Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Orquídea Cattleya',
            'slug' => 'orquidea-cattleya-' . uniqid(),
            'price' => 45000,
            'stock' => 10,
            'category' => 'Viveros',
            'is_active' => true,
        ]);

        $consumer = User::create([
            'name'     => 'Cliente Test',
            'email'    => 'cliente_' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'consumer',
            'phone'    => '3109998877',
            'email_verified_at' => now(),
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $consumer->id,
            'total' => 45000,
            'subtotal' => 45000,
            'status' => 'shipped',
            'payment_status' => 'paid',
            'tracking_number' => 'ENVIA-TEST-998877',
            'shipping_carrier' => 'servientrega',
            'shipping_address' => 'Carrera 6 # 10-20, Fusagasugá',
        ]);
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 45000,
        ]);

        // 1. Rastreo del pedido por parte del consumidor
        $resConsumer = $this->actingAs($consumer)->getJson(route('consumer.orders.tracking', $order->id));
        $resConsumer->assertOk()
            ->assertJsonStructure(['tracking_number', 'carrier', 'step', 'events', 'status'])
            ->assertJson([
                'tracking_number' => 'ENVIA-TEST-998877',
                'carrier' => 'SERVIENTREGA',
            ]);

        // 2. Rastreo del pedido por parte del comerciante
        $resMerchant = $this->actingAs($merchant)->getJson(route('merchant.orders.tracking', $order->id));
        $resMerchant->assertOk()
            ->assertJsonStructure(['tracking_number', 'carrier', 'step', 'events', 'status'])
            ->assertJson([
                'tracking_number' => 'ENVIA-TEST-998877',
                'carrier' => 'SERVIENTREGA',
            ]);

        // 3. Vista de órdenes del comerciante: no debe tener selector manual de status
        $resOrdersView = $this->actingAs($merchant)->get(route('merchant.orders'));
        $resOrdersView->assertOk();
        $resOrdersView->assertSee('ENVIA-TEST-998877');
        $resOrdersView->assertSee('Ver Rastreo Envia.com');
        $resOrdersView->assertDontSee('<select name="status"', false);
    }
}
