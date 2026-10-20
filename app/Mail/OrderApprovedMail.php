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
    public function __construct(
        public Order $order,
        public string $recipientType = 'consumer',
        public ?string $recipientName = null
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = match ($this->recipientType) {
            'merchant' => "Venta y Factura Electrónica DIAN · Pedido {$this->order->reference}",
            'admin'    => "Copia E-Commerce Factura DIAN · Pedido {$this->order->reference}",
            default    => "Compra aprobada y Factura Electrónica · Pedido {$this->order->reference}",
        };

        return new Envelope(subject: "{$prefix} · FusaShop");
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
