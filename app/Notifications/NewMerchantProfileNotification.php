<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMerchantProfileNotification extends Notification
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
        return (new MailMessage)
            ->subject('Nueva Solicitud de Verificación - ' . $this->profile->company_name)
            ->greeting('Hola Analista,')
            ->line('El comerciante "' . $this->profile->company_name . '" ha enviado sus documentos para verificación (KYC).')
            ->action('Revisar Solicitud', route('analyst.dashboard'))
            ->line('Por favor, revisa el RUT y la Cámara de Comercio para proceder con la aprobación.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_merchant_kyc',
            'company_name' => $this->profile->company_name,
            'merchant_id' => $this->profile->merchant_id,
            'message' => 'Nueva solicitud de verificación de ' . $this->profile->company_name,
        ];
    }
}
