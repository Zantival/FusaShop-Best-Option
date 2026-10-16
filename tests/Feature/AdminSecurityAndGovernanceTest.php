<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSecurityAndGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_and_merchant_receive_403_on_all_admin_routes(): void
    {
        $consumer = User::create([
            'name' => 'Consumidor Test',
            'email' => 'consumidor_test@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'consumer',
            'email_verified_at' => now(),
        ]);

        $merchant = User::create([
            'name' => 'Comerciante Test',
            'email' => 'comerciante_test@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'merchant',
            'email_verified_at' => now(),
        ]);

        $adminGetRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/users/create',
            '/admin/orders',
            '/admin/payments',
            '/admin/integrations',
            '/admin/sales-report-print',
            '/admin/reviews-report',
            '/admin/banners',
            '/admin/pqrs',
            '/admin/audit-logs',
        ];

        foreach ($adminGetRoutes as $uri) {
            $this->actingAs($consumer)->get($uri)->assertStatus(403);
            $this->actingAs($merchant)->get($uri)->assertStatus(403);
        }
    }

    public function test_analyst_can_access_dashboard_and_causal_reports_and_soft_delete_preserves_user(): void
    {
        $admin = User::create([
            'name' => 'Santi Super Admin',
            'email' => 'santi_admin@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'analyst',
            'admin_subrole' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $targetUser = User::create([
            'name' => 'Vendedor Borrado Logico',
            'email' => 'vendedor_soft@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'merchant',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/sales-report-print')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/reviews-report')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/audit-logs')->assertStatus(200);

        // SoftDelete test
        $this->actingAs($admin)
            ->delete("/admin/users/{$targetUser->id}", ['reason' => 'Prueba de borrado lógico'])
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.soft_deleted',
            'target_id' => $targetUser->id,
        ]);
    }

    public function test_analyst_can_send_sales_report_notification_and_merchant_can_view_report(): void
    {
        $admin = User::create([
            'name' => 'Santi Super Admin',
            'email' => 'santi_report_admin@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'analyst',
            'admin_subrole' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $merchant = User::create([
            'name' => 'Carlos Tienda Andina',
            'email' => 'tienda_andina@fusashop.com',
            'password' => Hash::make('password123'),
            'role' => 'merchant',
            'email_verified_at' => now(),
        ]);

        \App\Models\CompanyProfile::create([
            'merchant_id' => $merchant->id,
            'company_name' => 'Tienda Andina',
            'nit' => '900123456-1',
            'business_type' => 'Agropecuario',
            'address' => 'Vereda Pekín, Fusagasugá',
            'phone' => '3101234567',
            'rut_path' => 'ruts/test.pdf',
            'kyc_status' => 'approved',
        ]);

        // 1. El admin envía el reporte de ventas y diagnóstico causal al comerciante
        $response = $this->actingAs($admin)->postJson("/admin/merchants/{$merchant->id}/send-report", [
            'report_type' => 'diagnostic',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        // 2. El comerciante recibe la notificación real en la base de datos
        $notification = $merchant->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('merchant_sales_report', $notification->data['type'] ?? null);
        $this->assertNull($notification->read_at);

        // 3. El comerciante inicia sesión, ve la notificación en su bandeja y abre el reporte
        $this->actingAs($merchant)
            ->get('/notifications')
            ->assertStatus(200)
            ->assertSee('Abrir Reporte de Ventas y Diagnóstico Causal');

        $reportResponse = $this->actingAs($merchant)
            ->get("/reporte-ventas-comercio/{$merchant->id}?notification_id={$notification->id}");

        $reportResponse->assertStatus(200)
            ->assertSee('Reporte de Ventas: Tienda Andina')
            ->assertSee('¿Por qué aumentaron o se impulsaron tus ventas?')
            ->assertSee('¿Por qué bajaron o qué frena tus ventas?');

        // 4. Al abrir el reporte, la notificación queda marcada como leída
        $this->assertNotNull($notification->fresh()->read_at);
    }
}

