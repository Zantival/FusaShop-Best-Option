<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

class OrderApprovedMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Compra aprobada · Pedido {$this->order->reference} · FusaShop");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-approved');
    }

    public function attachments(): array
    {
        $out = [];
        foreach (['invoice_pdf_path' => 'application/pdf', 'invoice_xml_path' => 'application/xml'] as $field => $mime) {
            $path = $this->order->{$field};
            if ($path && Storage::disk('local')->exists($path)) {
                $out[] = Attachment::fromStorageDisk('local', $path)
                    ->as('factura-' . ($this->order->invoice_number ?? $this->order->reference) . '.' . pathinfo($path, PATHINFO_EXTENSION))
                    ->withMime($mime);
            }
        }
        return $out;
    }
}
