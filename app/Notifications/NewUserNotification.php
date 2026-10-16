<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo Usuario Registrado en FusaShop')
            ->greeting('Hola Analista,')
            ->line('Se ha registrado un nuevo usuario: ' . $this->user->name)
            ->line('Correo: ' . $this->user->email)
            ->line('Rol: ' . ucfirst($this->user->role))
            ->action('Ver Usuarios', route('analyst.users'))
            ->line('Gracias por supervisar la plataforma.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_user_registration',
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'user_role' => $this->user->role,
            'message' => 'Nuevo registro: ' . $this->user->name . ' (' . $this->user->role . ')',
        ];
    }
}
