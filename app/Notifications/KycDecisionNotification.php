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
        $statusLabel = match ($this->profile->kyc_status) {
            'approved' => 'Verificada ✓',
            'pending'  => 'En Revisión (Sin insignia de verificación)',
            default    => 'Rechazada',
        };

        $mail = (new MailMessage)
            ->subject('Estado de verificación de tu comercio en FusaShop: ' . $statusLabel)
            ->greeting('Hola ' . ($this->profile->user->name ?? 'Comerciante') . ',');

        if ($this->profile->kyc_status === 'approved') {
            $mail->line('¡Felicidades! El analista te ha otorgado el chulito de Comercio Verificado en FusaShop. Ahora tu tienda muestra la insignia oficial y puedes operar libremente.')
                 ->action('Ir al Dashboard', route('merchant.dashboard'));
        } elseif ($this->profile->kyc_status === 'pending') {
            $mail->line('El analista ha retirado temporalmente la insignia de verificación de tu comercio o tu cuenta se encuentra en revisión.')
                 ->line('Detalle: ' . ($this->profile->kyc_notes ?? 'Revisión administrativa de perfil comercial.'))
                 ->action('Ver Mi Perfil Comercial', route('merchant.profile'));
        } else {
            $mail->line('Lamentablemente, tu verificación de comerciante ha sido rechazada en este momento.')
                 ->line('Motivo: ' . ($this->profile->kyc_notes ?? 'No se proporcionaron detalles adicionales.'))
                 ->line('Por favor, revisa tus documentos y vuelve a intentarlo.')
                 ->action('Corregir Perfil', route('merchant.profile'));
        }

        return $mail->line('Gracias por ser parte de FusaShop.');
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->profile->kyc_status === 'approved';
        $isPending = $this->profile->kyc_status === 'pending';

        return [
            'type'    => 'kyc_decision',
            'status'  => $this->profile->kyc_status,
            'icon'    => $isApproved ? 'verified' : ($isPending ? 'info' : 'error'),
            'title'   => $isApproved
                ? '¡Comercio Verificado! ✓'
                : ($isPending ? 'Insignia de verificación retirada' : 'Verificación KYC rechazada'),
            'notes'   => $this->profile->kyc_notes,
            'message' => $isApproved
                ? 'El analista te ha otorgado el chulito de verificación oficial en FusaShop.'
                : ($isPending
                    ? 'El analista ha retirado el chulito de verificación de tu comercio.'
                    : 'Tu solicitud de verificación KYC ha sido rechazada: ' . ($this->profile->kyc_notes ?? '')),
            'url'     => $isApproved ? route('merchant.dashboard') : route('merchant.profile'),
        ];
    }
}
