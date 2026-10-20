<?php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderStatusChanged extends Notification
{
    public function __construct(public Order $order) {}

    public function via($notifiable): array { return ['database', 'mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $labels = [
            'pending'    => 'Pendiente',
            'processing' => 'En preparación por el comercio',
            'shipped'    => 'Despachado / En tránsito',
            'delivered'  => 'Entregado al comprador',
            'cancelled'  => 'Cancelado',
        ];
        
        $status = $labels[$this->order->status] ?? $this->order->status;
        
        $mail = (new MailMessage)
            ->subject("Actualización de tu pedido #{$this->order->id} en FusaShop")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("El estado de tu pedido (**#{$this->order->id}** / Ref: **{$this->order->reference}**) ahora es: **{$status}**.");

        if ($this->order->tracking_number) {
            $carrier = strtoupper($this->order->shipping_carrier ?: 'Envia.com');
            $mail->line("🚚 **Transportadora:** {$carrier} · **Guía:** `{$this->order->tracking_number}`");
        }

        if ($this->order->tracking_url) {
            $mail->action('Rastrear Envío en Vivo', $this->order->tracking_url);
        } else {
            $mail->action('Ver Detalles del Pedido', route('consumer.orders.receipt', $this->order->id));
        }

        $mail->line('Gracias por apoyar el comercio y turismo local de Fusagasugá con FusaShop.');

        return $mail;
    }

    public function toArray($notifiable): array
    {
        $labels = [
            'pending'    => 'Pendiente',
            'processing' => 'En proceso',
            'shipped'    => 'Enviado',
            'delivered'  => 'Entregado',
            'cancelled'  => 'Cancelado',
        ];
        $icons = [
            'pending'    => 'schedule',
            'processing' => 'sync',
            'shipped'    => 'local_shipping',
            'delivered'  => 'check_circle',
            'cancelled'  => 'cancel',
        ];
        $status = $this->order->status;
        return [
            'title'    => 'Pedido #' . $this->order->id . ' actualizado',
            'message'  => 'El estado de tu pedido cambió a: ' . ($labels[$status] ?? $status),
            'icon'     => $icons[$status] ?? 'info',
            'order_id' => $this->order->id,
            'url'      => route('consumer.orders'),
        ];
    }
}
