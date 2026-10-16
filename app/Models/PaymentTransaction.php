<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    public const FINAL_STATUSES = ['APPROVED', 'DECLINED', 'VOIDED', 'ERROR'];

    protected $fillable = [
        'order_id', 'transaction_id', 'reference', 'status',
        'amount_in_cents', 'currency', 'payload', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function order() { return $this->belongsTo(Order::class); }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true) && $this->processed_at !== null;
    }
}
