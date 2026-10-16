<?php

namespace App\Notifications;

use App\Models\PQRS;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPQRSNotification extends Notification
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
            ->subject('Nueva PQRS Recibida - ' . ucfirst($this->pqrs->type))
            ->line('Se ha recibido una nueva solicitud de tipo ' . ucfirst($this->pqrs->type) . '.')
            ->line('Usuario: ' . $this->pqrs->user->name)
            ->line('Asunto: ' . $this->pqrs->subject)
            ->action('Ver Detalle', url('/admin/pqrs/' . $this->pqrs->id))
            ->line('Por favor, revisa la solicitud lo antes posible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva PQRS: ' . ucfirst($this->pqrs->type),
            'message' => $this->pqrs->user->name . ' ha enviado una ' . $this->pqrs->type . '.',
            'icon' => 'assignment',
            'pqrs_id' => $this->pqrs->id
        ];
    }
}
