<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BannerRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $bannerRequest;

    public function __construct($bannerRequest)
    {
        $this->bannerRequest = $bannerRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nueva solicitud de Banner Publicitario',
            'message' => 'El comerciante ' . ($this->bannerRequest->user->name ?? '') . ' ha solicitado espacio para un banner en la página principal.',
            'type' => 'banner_request',
            'request_id' => $this->bannerRequest->id,
            'url' => route('analyst.banner-requests.show', $this->bannerRequest->id),
        ];
    }
}
