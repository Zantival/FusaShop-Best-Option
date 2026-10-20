<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantCustomerConversationsTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private User $consumer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::create([
            'name' => 'Fusa Flores y Más',
            'email' => 'flores@fusa.com',
            'password' => 'secret123',
            'role' => 'merchant',
            'phone' => '3119876543',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'user_id' => $this->merchant->id,
            'merchant_id' => $this->merchant->id,
            'company_name' => 'Fusa Flores y Más',
            'phone' => '3119876543',
            'address' => 'Calle 8 # 5-20, Fusagasugá',
            'rut_path' => 'ruts/flores.pdf',
            'kyc_status' => 'approved',
            'status' => 'approved',
        ]);

        $this->consumer = User::create([
            'name' => 'Camila Rodriguez',
            'email' => 'camila@cliente.com',
            'password' => 'secret123',
            'role' => 'consumer',
            'phone' => '3007654321',
            'email_verified_at' => now(),
        ]);

        $this->product = Product::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Arreglo Floral Sumapaz',
            'slug' => 'arreglo-floral-sumapaz',
            'price' => 65000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_conversations_with_customer_and_product_details(): void
    {
        // El cliente envía un mensaje sobre el producto
        $msg = Message::create([
            'sender_id' => $this->consumer->id,
            'receiver_id' => $this->merchant->id,
            'product_id' => $this->product->id,
            'content' => '¡Hola! ¿Tienen entrega hoy mismo en el centro de Fusagasugá?',
            'is_read' => false,
        ]);

        // El comerciante visita la sección de mensajes
        $response = $this->actingAs($this->merchant)->get(route('merchant.messages'));

        $response->assertOk();
        $response->assertSee('Conversaciones con Clientes');
        $response->assertSee('Camila Rodriguez');
        $response->assertSee('Arreglo Floral Sumapaz');
        $response->assertSee('¿Tienen entrega hoy mismo en el centro de Fusagasugá?');
        $response->assertSee('WhatsApp');

        // El mensaje debe marcarse como leído
        $this->assertTrue($msg->fresh()->is_read);
    }

    public function test_merchant_can_send_reply_to_customer(): void
    {
        $response = $this->actingAs($this->merchant)->postJson(route('chat.store'), [
            'receiver_id' => $this->consumer->id,
            'content' => '¡Hola Camila! Sí, entregamos hoy mismo mediante Envia.com.',
            'product_id' => $this->product->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->merchant->id,
            'receiver_id' => $this->consumer->id,
            'content' => '¡Hola Camila! Sí, entregamos hoy mismo mediante Envia.com.',
            'product_id' => $this->product->id,
        ]);
    }

    public function test_merchant_visiting_generic_chat_is_redirected_to_merchant_messages(): void
    {
        $responseIndex = $this->actingAs($this->merchant)->get(route('chat.index'));
        $responseIndex->assertRedirect(route('merchant.messages'));

        $responseShow = $this->actingAs($this->merchant)->get(route('chat.show', $this->consumer->id));
        $responseShow->assertRedirect(route('merchant.messages', $this->consumer->id));
    }

    public function test_merchant_sidebar_displays_customer_messages_link_and_badge(): void
    {
        Message::create([
            'sender_id' => $this->consumer->id,
            'receiver_id' => $this->merchant->id,
            'content' => 'Consulta pendiente',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));
        $response->assertOk();
        $response->assertSee('Mensajes con Clientes');
        $response->assertSee(route('merchant.messages'));
    }

    public function test_customer_message_notification_is_clickable_and_disappears_after_reading(): void
    {
        // El cliente envía un mensaje por el controlador de chat (genera Message + NewMessageNotification)
        $this->actingAs($this->consumer)->postJson(route('chat.store'), [
            'receiver_id' => $this->merchant->id,
            'content' => 'Mensaje exclusivo de prueba para campana',
            'product_id' => $this->product->id,
        ])->assertOk();

        $notif = $this->merchant->unreadNotifications()->first();
        $this->assertNotNull($notif);

        // 1. Antes de verlo, aparece en las notificaciones del sidebar con enlace clicable
        $dashBefore = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));
        $dashBefore->assertOk();
        $dashBefore->assertSee('Mensaje exclusivo de prueba para campana');
        $dashBefore->assertSee(route('notifications.open', $notif->id));

        // 2. Al darle clic a la notificación, redirige a la conversación del comerciante y la marca como leída
        $openRes = $this->actingAs($this->merchant)->get(route('notifications.open', $notif->id));
        $openRes->assertRedirect(route('merchant.messages', $this->consumer->id));
        $this->assertNotNull($notif->fresh()->read_at);

        // 3. Después de haberlo visto, ya no aparece en las notificaciones
        $dashAfter = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));
        $dashAfter->assertOk();
        $dashAfter->assertDontSee('Mensaje exclusivo de prueba para campana');
    }

    public function test_merchant_contact_support_opens_analyst_support_chat_not_customer_messages(): void
    {
        $analyst = User::create([
            'name' => 'Carlos Analista Sumapaz',
            'email' => 'analista@fusashop.com',
            'password' => 'secret123',
            'role' => 'analyst',
            'phone' => '3101112233',
            'email_verified_at' => now(),
        ]);

        // 1. Al hacer clic en "Soporte Técnico", se abre directamente la vista de soporte con el analista
        $supportResponse = $this->actingAs($this->merchant)->get(route('merchant.support.contact'));
        $supportResponse->assertOk();
        $supportResponse->assertSee('Soporte Técnico & Comunicación con el Analista', false);
        $supportResponse->assertSee('Carlos Analista Sumapaz');
        $supportResponse->assertDontSee('Aún no has recibido mensajes de clientes');

        // 2. Si el comerciante intenta abrir chat.show o merchant.messages con un analista, lo redirige a Soporte Técnico
        $this->actingAs($this->merchant)
            ->get(route('chat.show', $analyst->id))
            ->assertRedirect(route('merchant.support.contact', ['analyst' => $analyst->id]));

        $this->actingAs($this->merchant)
            ->get(route('merchant.messages', $analyst->id))
            ->assertRedirect(route('merchant.support.contact', ['analyst' => $analyst->id]));
    }
}

