<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShippedMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tu pedido {$this->order->reference} fue despachado · FusaShop");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-shipped');
    }
}
