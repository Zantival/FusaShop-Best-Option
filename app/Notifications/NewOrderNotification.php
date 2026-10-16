<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Nuevo Pedido Recibido en FusaShop!')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Has recibido un nuevo pedido (#' . $this->order->id . ') por un total de $' . number_format($this->order->total, 2) . '.')
            ->action('Ver Detalles del Pedido', route('merchant.orders'))
            ->line('Por favor, prepara el pedido lo antes posible.');
    }

    public function toArray($notifiable): array
    {
        return [
            'id' => $this->order->id,
            'title' => '¡Nuevo Pedido Recibido!',
            'message' => "Has recibido un nuevo pedido #{$this->order->id}. Revisa los detalles ahora.",
            'icon' => 'shopping_cart',
            'url' => route('merchant.orders'),
            'type' => 'new_order',
            'amount' => $this->order->total,
        ];
    }
}
