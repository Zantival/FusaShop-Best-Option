<?php

namespace App\Notifications;

use App\Models\PQRS;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PQRSResponseNotification extends Notification
{
    use Queueable;

    protected $pqrs;

    public function __construct(PQRS $pqrs)
    {
        $this->pqrs = $pqrs;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Respuesta a tu PQRS - ' . $this->pqrs->subject)
            ->line('Un administrador ha respondido a tu solicitud de ' . $this->pqrs->type . '.')
            ->line('Estado: ' . ucfirst($this->pqrs->status))
            ->action('Ver Respuesta', url('/pqrs/' . $this->pqrs->id))
            ->line('Gracias por ayudarnos a mejorar FusaShop.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Respuesta a tu PQRS',
            'message' => 'Tu solicitud "' . $this->pqrs->subject . '" ha sido respondida.',
            'icon' => 'feedback',
            'pqrs_id' => $this->pqrs->id
        ];
    }
}
