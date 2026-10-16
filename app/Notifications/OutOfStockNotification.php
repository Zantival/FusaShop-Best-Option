<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class OutOfStockNotification extends Notification
{
    public function __construct(public Product $product) {}

    public function via($notifiable): array { return ['database', 'mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Alerta de Inventario: Producto Agotado!')
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu producto "**' . $this->product->name . '**" se ha quedado sin stock (0 unidades disponibles).')
            ->line('Los clientes no podrán comprar este producto hasta que actualices el inventario.')
            ->action('Actualizar Inventario', route('merchant.inventory'))
            ->line('Gracias por usar FusaShop.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title'      => '¡Producto Agotado!',
            'message'    => "Tu producto '{$this->product->name}' se ha quedado sin stock.",
            'icon'       => 'warning',
            'product_id' => $this->product->id,
            'url'        => route('merchant.inventory'),
        ];
    }
}
