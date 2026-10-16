<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'sender_id' => $this->message->sender_id,
            'title'     => 'Nuevo mensaje de ' . $this->message->sender->name,
            'message'   => str($this->message->content)->limit(50),
            'url'       => route('chat.show', $this->message->sender_id),
            'icon'      => 'chat_bubble',
        ];
    }
}
