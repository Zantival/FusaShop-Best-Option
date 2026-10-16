<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a FusaShop!')
            ->greeting('Hola ' . $notifiable->name . '!')
            ->line('Estamos emocionados de tenerte con nosotros en FusaShop, la plataforma que conecta a los productores locales con compradores conscientes.')
            ->line('Tu cuenta ha sido creada exitosamente.')
            ->action('Explorar el Catálogo', url('/shop/catalog'))
            ->line('¡Gracias por apoyar el comercio local de Fusagasugá!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => '¡Bienvenido!',
            'message' => 'Tu cuenta ha sido creada con éxito. ¡Explora los productos locales!',
            'icon' => 'waving_hand'
        ];
    }
}
