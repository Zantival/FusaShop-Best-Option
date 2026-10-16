<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id','total','discount','points_used','points_earned',
        'status','shipping_address','payment_method',
        'payment_status','notes',
        'reference','subtotal','shipping_cost','commission_amount','currency',
        'shipping_address_id','shipping_snapshot','shipping_postal_code','shipping_carrier','shipping_service',
        'customer_document_type','customer_document',
        'wompi_transaction_id','wompi_status','stock_reserved','reservation_expires_at','paid_at','packed_at',
        'tracking_number','tracking_url','label_url','tracking_status','shipped_at','delivered_at',
        'invoice_number','invoice_cufe','invoice_pdf_path','invoice_xml_path','invoiced_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'shipping_snapshot' => 'array',
            'stock_reserved' => 'boolean',
            'reservation_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'invoiced_at' => 'datetime',
        ];
    }

    // status: pending | processing | shipped | delivered | cancelled
    // payment_status: pending | paid | failed
    // Etapas visibles: Pendiente(pending) · Pagado(processing) · En empaque(processing+packed_at)
    //                  · Enviado(shipped) · Entregado(delivered)

    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function transactions() { return $this->hasMany(PaymentTransaction::class); }
    public function integrationLogs() { return $this->hasMany(IntegrationLog::class); }

    public function totalInCents(): int
    {
        return (int) round(((float) $this->total) * 100);
    }

    public function stageLabel(): string
    {
        return match (true) {
            $this->status === 'cancelled' => 'Cancelado',
            $this->status === 'delivered' => 'Entregado',
            $this->status === 'shipped' => 'Enviado',
            $this->status === 'processing' && $this->packed_at !== null => 'En empaque',
            $this->payment_status === 'paid' => 'Pagado',
            default => 'Pendiente',
        };
    }

    public function trackingStep(): int
    {
        return match (true) {
            $this->status === 'delivered' => 4,
            $this->status === 'shipped' || !empty($this->tracking_number) => 3,
            $this->packed_at !== null || $this->status === 'processing' => 2,
            $this->payment_status === 'paid' => 1,
            default => 0,
        };
    }
}
