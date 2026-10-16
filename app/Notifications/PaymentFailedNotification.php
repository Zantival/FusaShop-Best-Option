<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
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
            ->subject("No pudimos procesar tu pago · {$this->order->reference}")
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line("El pago del pedido {$this->order->reference} fue rechazado o falló ({$this->order->wompi_status}).")
            ->line('Liberamos las unidades reservadas. Puedes intentarlo de nuevo con otro medio de pago.')
            ->action('Volver a la tienda', route('consumer.catalog'));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Pago no procesado',
            'message' => "El pago del pedido {$this->order->reference} no fue aprobado.",
            'icon' => 'error',
            'order_id' => $this->order->id,
        ];
    }
}
