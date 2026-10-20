<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\MerchantSalesReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantSalesReportCenterTest extends TestCase
{
    use RefreshDatabase;

    private function createMerchantWithStore(string $name = 'Don Pedro', string $email = 'comerciante@test.com'): User
    {
        $merchant = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret123',
            'role' => 'merchant',
            'phone' => '3119876543',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'user_id' => $merchant->id,
            'merchant_id' => $merchant->id,
            'company_name' => 'Tienda ' . $name,
            'phone' => '3119876543',
            'address' => 'Calle 8 # 5-20, Fusagasugá',
            'rut_path' => 'ruts/tienda.pdf',
            'kyc_status' => 'approved',
            'status' => 'approved',
        ]);

        return $merchant;
    }

    public function test_merchant_can_access_reports_center_and_view_admin_reports(): void
    {
        $merchant = $this->createMerchantWithStore('Don Pedro', 'pedro@test.com');

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Camiseta Estampada',
            'slug' => 'camiseta-estampada',
            'price' => 45000,
            'stock' => 20,
            'is_active' => true,
        ]);

        $consumer = User::create([
            'name' => 'Carlos Gomez',
            'email' => 'carlos@cliente.com',
            'password' => 'secret123',
            'role' => 'consumer',
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $consumer->id,
            'reference' => 'ORD-1001',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'total' => 45000,
            'shipping_carrier' => 'Coordinadora',
            'tracking_number' => 'GUIA-889900',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => 45000,
            'subtotal' => 45000,
        ]);

        // Simular notificación enviada por el Administrador / Analista
        $merchant->notify(new MerchantSalesReportNotification([
            'report_type' => 'diagnostic',
            'company_name' => 'Tienda Don Pedro',
            'summary_reason' => 'Excelente balance mensual, mantén promociones activas este fin de semana.',
            'total_sales' => 45000,
            'variation_pct' => 15.0,
            'report_url' => route('merchant.sales-report.show', $merchant->id),
        ]));

        $response = $this->actingAs($merchant)->get(route('merchant.reports'));

        $response->assertStatus(200);
        $response->assertSee('Reportes de Ventas del Negocio');
        $response->assertSee('Informes Oficiales Emitidos por el Administrador');
        $response->assertSee('Excelente balance mensual, mantén promociones activas este fin de semana.');
        $response->assertSee('Descargar CSV (Excel)');
        $response->assertSee('Centro de Descarga de Datos de Ventas');
        $response->assertSee(route('merchant.reports.export-csv'));
        $response->assertSee(route('merchant.sales-report.show', $merchant->id));
    }

    public function test_merchant_can_download_sales_report_csv(): void
    {
        $merchant = $this->createMerchantWithStore('Don Pedro', 'pedro2@test.com');

        $product = Product::create([
            'merchant_id' => $merchant->id,
            'name' => 'Café Especial Sumapaz 500g',
            'slug' => 'cafe-especial-sumapaz-500g',
            'price' => 28000,
            'stock' => 15,
            'is_active' => true,
        ]);

        // Otro producto de otro comerciante
        $otherMerchant = $this->createMerchantWithStore('Don Juan', 'juan@test.com');
        $otherProduct = Product::create([
            'merchant_id' => $otherMerchant->id,
            'name' => 'Panela Orgánica Ajena',
            'slug' => 'panela-organica-ajena',
            'price' => 8000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $consumer = User::create([
            'name' => 'Laura Perez',
            'email' => 'laura@cliente.com',
            'password' => 'secret123',
            'role' => 'consumer',
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $consumer->id,
            'reference' => 'ORD-2002',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total' => 36000,
            'shipping_carrier' => 'Interrapidisimo',
            'tracking_number' => 'INTER-776655',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'price' => 28000,
            'subtotal' => 56000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $otherProduct->id,
            'product_name' => $otherProduct->name,
            'quantity' => 1,
            'price' => 8000,
            'subtotal' => 8000,
        ]);

        $response = $this->actingAs($merchant)->get(route('merchant.reports.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();

        // Verificar encabezados del CSV
        $this->assertStringContainsString('ID Pedido', $content);
        $this->assertStringContainsString('Referencia', $content);
        $this->assertStringContainsString('Fecha Pedido', $content);
        $this->assertStringContainsString('Cliente', $content);
        $this->assertStringContainsString('Email Cliente', $content);
        $this->assertStringContainsString('Producto', $content);
        $this->assertStringContainsString('Transportadora Envia.com', $content);
        $this->assertStringContainsString('Numero de Guia', $content);
        
        // Debe contener el producto del comerciante
        $this->assertStringContainsString('Café Especial Sumapaz 500g', $content);
        $this->assertStringContainsString('ORD-2002', $content);
        $this->assertStringContainsString('Laura Perez', $content);
        $this->assertStringContainsString('INTER-776655', $content);

        // No debe contener el producto ajeno
        $this->assertStringNotContainsString('Panela Orgánica Ajena', $content);
    }

    public function test_consumer_cannot_access_merchant_reports_or_export(): void
    {
        $consumer = User::create([
            'name' => 'Consumidor Simple',
            'email' => 'simple@test.com',
            'password' => 'secret123',
            'role' => 'consumer',
            'email_verified_at' => now(),
        ]);

        $res1 = $this->actingAs($consumer)->get(route('merchant.reports'));
        $this->assertTrue(in_array($res1->status(), [403, 302]));

        $res2 = $this->actingAs($consumer)->get(route('merchant.reports.export-csv'));
        $this->assertTrue(in_array($res2->status(), [403, 302]));
    }

    public function test_notification_is_clickable_and_disappears_from_notifications_once_viewed(): void
    {
        $merchant = $this->createMerchantWithStore('Tienda Andina', 'andina@test.com');

        $merchant->notify(new MerchantSalesReportNotification([
            'report_type' => 'diagnostic',
            'company_name' => 'Tienda Andina',
            'summary_reason' => 'Alerta única de variación en ventas para verificar lectura.',
            'total_sales' => 90000,
            'variation_pct' => -5.0,
            'report_url' => route('merchant.sales-report.show', $merchant->id),
        ]));

        $notif = $merchant->unreadNotifications()->first();
        $this->assertNotNull($notif);

        // 1. Mientras no se ha visto, aparece en el panel del comerciante con enlace clicable
        $dashboardBefore = $this->actingAs($merchant)->get(route('merchant.dashboard'));
        $dashboardBefore->assertOk();
        $dashboardBefore->assertSee('Alerta única de variación en ventas para verificar lectura.');
        $dashboardBefore->assertSee(route('notifications.open', $notif->id));

        // 2. Al hacer clic en la notificación, redirige al reporte para verlo y la marca como leída
        $openResponse = $this->actingAs($merchant)->get(route('notifications.open', $notif->id));
        $openResponse->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);

        // 3. Una vez vista, ya NO debe aparecer en las notificaciones del menú ni en /notifications
        $dashboardAfter = $this->actingAs($merchant)->get(route('merchant.dashboard'));
        $dashboardAfter->assertOk();
        $dashboardAfter->assertDontSee('Alerta única de variación en ventas para verificar lectura.');

        $notifsPageAfter = $this->actingAs($merchant)->get(route('notifications.index'));
        $notifsPageAfter->assertOk();
        $notifsPageAfter->assertDontSee('Alerta única de variación en ventas para verificar lectura.');
    }
}
