<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Models\Review;

class NewProductReview extends Notification
{
    public function __construct(public Review $review) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array
    {
        return [
            'title'      => 'Nueva valoración en ' . e($this->review->product->name),
            'message'    => $this->review->user->name . ' dejó ' . $this->review->rating . '★: ' . (strlen($this->review->comment ?? '') > 80 ? substr($this->review->comment,0,80).'…' : ($this->review->comment ?: '(sin comentario)')),
            'icon'       => 'star',
            'product_id' => $this->review->product_id,
        ];
    }
}
