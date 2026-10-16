<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycDecisionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $profile;

    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->profile->kyc_status === 'approved' ? 'Aprobada' : 'Rechazada';
        $mail = (new MailMessage)
            ->subject('Tu solicitud de verificación KYC ha sido ' . $status)
            ->greeting('Hola ' . $this->profile->user->name . ',');

        if ($this->profile->kyc_status === 'approved') {
            $mail->line('¡Felicidades! Tu perfil de comerciante ha sido aprobado. Ahora puedes publicar productos y vender en FusaShop.')
                 ->action('Ir al Dashboard', route('merchant.dashboard'));
        } else {
            $mail->line('Lamentablemente, tu perfil de comerciante no ha sido aprobado en este momento.')
                 ->line('Motivo del rechazo: ' . ($this->profile->kyc_notes ?? 'No se proporcionaron detalles adicionales.'))
                 ->line('Por favor, revisa tus documentos y vuelve a intentarlo.')
                 ->action('Corregir Perfil', route('merchant.profile'));
        }

        return $mail->line('Gracias por ser parte de FusaShop.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc_decision',
            'status' => $this->profile->kyc_status,
            'notes' => $this->profile->kyc_notes,
            'message' => 'Tu solicitud KYC ha sido ' . ($this->profile->kyc_status === 'approved' ? 'aprobada' : 'rechazada'),
        ];
    }
}
