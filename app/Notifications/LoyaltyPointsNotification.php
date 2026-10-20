<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoyaltyPointsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public int $pointsEarned = 0,
        public int $pointsUsed = 0,
        public float $discountAmount = 0.0
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Tus Puntos FusaCoins y Descuentos · Pedido ' . $this->order->reference)
            ->greeting('¡Hola ' . $notifiable->name . '!');

        if ($this->pointsUsed > 0 && $this->discountAmount > 0) {
            $mail->line('En tu pedido **' . $this->order->reference . '** redimiste **' . $this->pointsUsed . ' FusaCoins**, obteniendo un descuento directo de **$' . number_format($this->discountAmount, 0, ',', '.') . ' COP**.');
        }

        if ($this->pointsEarned > 0) {
            $mail->line('Además, por tu compra acabas de ganar **+' . $this->pointsEarned . ' FusaCoins** (equivalentes a $' . number_format($this->pointsEarned * 100, 0, ',', '.') . ' COP para tus próximas compras en comercios de Fusagasugá).');
        }

        return $mail
            ->action('Ver Mis Pedidos y Puntos', route('consumer.orders'))
            ->line('¡Gracias por apoyar a los comercios locales en FusaShop!');
    }

    public function toArray($notifiable): array
    {
        $parts = [];
        if ($this->pointsUsed > 0) {
            $parts[] = "Redimiste {$this->pointsUsed} FusaCoins (-$" . number_format($this->discountAmount, 0, ',', '.') . " COP)";
        }
        if ($this->pointsEarned > 0) {
            $parts[] = "Ganaste +{$this->pointsEarned} FusaCoins";
        }

        return [
            'order_id'      => $this->order->id,
            'title'         => ' Movimiento de Puntos FusaCoins',
            'message'       => implode(' · ', $parts) . " en el pedido {$this->order->reference}.",
            'icon'          => 'stars',
            'type'          => 'loyalty_points',
            'points_earned' => $this->pointsEarned,
            'points_used'   => $this->pointsUsed,
            'discount'      => $this->discountAmount,
            'url'           => route('consumer.orders'),
        ];
    }
}
