<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class AdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_opens_the_admin_dashboard_even_if_shop_was_the_intended_url(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        User::create([
            'name' => 'FusaShop Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $response = $this
            ->withSession(['url.intended' => route('consumer.home')])
            ->post(route('login.post'), [
                'email' => 'admin@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('analyst.dashboard'));
        $this->assertAuthenticated();

        $this->get(route('analyst.dashboard'))
            ->assertOk()
            ->assertSee('Panel Ejecutivo Fusagasugá');
    }
}
