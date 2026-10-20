<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledByBuyerNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $reason = 'Cancelado por el comprador')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $buyerName = $this->order->user->name ?? 'El comprador';

        return (new MailMessage)
            ->subject('Aviso de Cancelación: Pedido #' . $this->order->id . ' en FusaShop')
            ->greeting('Hola ' . ($notifiable->name ?? 'Comerciante') . ',')
            ->line("Te informamos que {$buyerName} ha cancelado el Pedido #{$this->order->id}.")
            ->line('Motivo registrado: ' . $this->reason)
            ->line('El stock de tus productos asociados a esta orden ha sido restituido automáticamente en tu inventario.')
            ->action('Ver Pedidos Cancelados', route('merchant.orders', ['status' => 'cancelled']))
            ->line('Gracias por vender en FusaShop.');
    }

    public function toArray(object $notifiable): array
    {
        $buyerName = $this->order->user->name ?? 'El comprador';

        return [
            'type'     => 'order_cancelled',
            'order_id' => $this->order->id,
            'icon'     => 'cancel',
            'title'    => "Pedido #{$this->order->id} Cancelado",
            'message'  => "{$buyerName} canceló el Pedido #{$this->order->id} ({$this->reason}). Tu stock fue restituido automáticamente.",
            'url'      => route('merchant.orders', ['status' => 'cancelled']),
        ];
    }
}
