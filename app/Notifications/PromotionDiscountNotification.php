<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionDiscountNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $subtitle,
        public string $discountBadge,
        public string $category,
        public ?int $productId = null,
        public ?string $targetUrl = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->targetUrl
            ?: ($this->productId ? route('consumer.product', $this->productId) : route('consumer.catalog', ['category' => $this->category]));

        return (new MailMessage)
            ->subject("🔥 {$this->discountBadge} en {$this->category} · {$this->title}")
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line("Tenemos una nueva promoción activa en FusaShop: **{$this->title}** ({$this->discountBadge}).")
            ->line($this->subtitle)
            ->action('Aprovechar Descuento Ahora', $url)
            ->line('Recuerda que también puedes acumular y redimir tus puntos FusaCoins en el checkout.');
    }

    public function toArray($notifiable): array
    {
        $url = $this->targetUrl
            ?: ($this->productId ? route('consumer.product', $this->productId) : route('consumer.catalog', ['category' => $this->category]));

        return [
            'title'          => "🔥 {$this->discountBadge}: {$this->title}",
            'message'        => $this->subtitle,
            'icon'           => 'local_offer',
            'type'           => 'promotion_discount',
            'category'       => $this->category,
            'discount_badge' => $this->discountBadge,
            'product_id'     => $this->productId,
            'url'            => $url,
        ];
    }
}
