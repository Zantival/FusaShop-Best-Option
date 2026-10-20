<?php

namespace Tests\Feature;

use App\Mail\LowStockAlertMail;
use App\Mail\OrderShippedMail;
use App\Mail\VerifyEmailCodeMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SixDigitEmailVerificationAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_generates_six_digit_code_and_sends_email(): void
    {
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $response = $this->post(route('register.post'), [
            'name'                  => 'Alejandro Rojas',
            'email'                 => 'alejandro@fusagasuga.com',
            'phone'                 => '3123456789',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'consumer',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'alejandro@fusagasuga.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->verification_code);
        $this->assertEquals(6, strlen($user->verification_code));
        $this->assertTrue($user->verification_code_expires_at->isFuture());

        Mail::assertSent(VerifyEmailCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->code === $user->verification_code;
        });
    }

    public function test_verify_email_page_renders_six_digit_form(): void
    {
        $user = User::create([
            'name'              => 'Test User',
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'role'              => 'consumer',
            'phone'             => '3001234567',
            'email_verified_at' => null,
            'verification_code' => '456789',
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertOk();
        $response->assertSee('Ingresa tu código de 6 números');
        $response->assertSee(route('verification.verify-code'));
        $response->assertSee(route('verification.resend-code'));
    }

    public function test_submitting_incorrect_code_fails(): void
    {
        $user = User::create([
            'name'                         => 'Test User',
            'email'                        => 'test2@example.com',
            'password'                     => Hash::make('password123'),
            'role'                         => 'consumer',
            'phone'                        => '3001234567',
            'email_verified_at'            => null,
            'verification_code'            => '123456',
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->post(route('verification.verify-code'), [
            'code' => '999999',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_submitting_correct_code_verifies_account_and_redirects(): void
    {
        $user = User::create([
            'name'                         => 'Test User',
            'email'                        => 'test3@example.com',
            'password'                     => Hash::make('password123'),
            'role'                         => 'consumer',
            'phone'                        => '3001234567',
            'email_verified_at'            => null,
            'verification_code'            => '789123',
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->actingAs($user)->post(route('verification.verify-code'), [
            'code' => '789123',
        ]);

        $response->assertRedirect(route('consumer.home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->verification_code);
    }

    public function test_resending_code_generates_new_otp_and_sends_email(): void
    {
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $user = User::create([
            'name'              => 'Test User',
            'email'             => 'test4@example.com',
            'password'          => Hash::make('password123'),
            'role'              => 'consumer',
            'phone'             => '3001234567',
            'email_verified_at' => null,
            'verification_code' => '111111',
        ]);

        $response = $this->actingAs($user)->post(route('verification.resend-code'));
        $response->assertSessionHas('status', 'verification-code-sent');

        $user->refresh();
        $this->assertNotEquals('111111', $user->verification_code);
        $this->assertEquals(6, strlen($user->verification_code));

        Mail::assertSent(VerifyEmailCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->code === $user->verification_code;
        });
    }

    public function test_verification_email_contains_brand_colors_and_welcome(): void
    {
        $user = User::create([
            'name'     => 'Luisa Fernanda',
            'email'    => 'luisa@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'merchant',
            'phone'    => '3001234567',
        ]);
        $mailable = new VerifyEmailCodeMail($user, '852963');

        $rendered = $mailable->render();

        $this->assertStringContainsString('Bienvenido a', $rendered);
        $this->assertStringContainsString('color:#10B981;', $rendered);
        $this->assertStringContainsString('color:#2563EB;', $rendered);
        $this->assertStringContainsString('color:#F97316;', $rendered);
        $this->assertStringContainsString('852963', $rendered);
    }

    public function test_low_stock_email_contains_fusashop_branding_and_product_details(): void
    {
        $merchant = User::create([
            'name'     => 'Finca La Palma',
            'email'    => 'lapalma@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'merchant',
            'phone'    => '3001234567',
        ]);
        $product = Product::create([
            'merchant_id'         => $merchant->id,
            'name'                => 'Café Especial Sumapaz 500g',
            'slug'                => 'cafe-especial-sumapaz-500g',
            'description'         => 'Café suave de origen Fusagasugá',
            'category'            => 'Café y Derivados',
            'price'               => 28000,
            'stock'               => 2,
            'low_stock_threshold' => 5,
            'status'              => 'active',
        ]);

        $mailable = new LowStockAlertMail($product);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Café Especial Sumapaz 500g', $rendered);
        $this->assertStringContainsString('color:#10B981;', $rendered);
        $this->assertStringContainsString('Alerta de Inventario Crítico', $rendered);
    }

    public function test_order_shipped_email_contains_carrier_and_tracking_link(): void
    {
        $consumer = User::create([
            'name'     => 'Andrés Cepeda',
            'email'    => 'andres@example.com',
            'password' => Hash::make('password123'),
            'role'     => 'consumer',
            'phone'    => '3001234567',
        ]);
        $order = Order::create([
            'user_id'          => $consumer->id,
            'reference'        => 'ORD-2026-TEST-99',
            'subtotal'         => 50000,
            'shipping_cost'    => 12000,
            'discount'         => 0,
            'total'            => 62000,
            'status'           => 'shipped',
            'payment_status'   => 'paid',
            'shipping_carrier' => 'servientrega',
            'tracking_number'  => 'SERVI-9876543210',
            'tracking_url'     => 'https://envia.com/rastreo?label=SERVI-9876543210',
        ]);

        $mailable = new OrderShippedMail($order);
        $rendered = $mailable->render();

        $this->assertStringContainsString('SERVI-9876543210', $rendered);
        $this->assertStringContainsString('SERVIENTREGA', $rendered);
        $this->assertStringContainsString('https://envia.com/rastreo?label=SERVI-9876543210', $rendered);
        $this->assertStringContainsString('color:#10B981;', $rendered);
    }
}
