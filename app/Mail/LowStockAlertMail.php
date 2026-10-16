<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LowStockAlertMail extends Mailable
{
    public function __construct(public Product $product)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Stock crítico: {$this->product->name}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.low-stock');
    }
}
