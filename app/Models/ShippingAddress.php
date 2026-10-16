<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'recipient', 'phone', 'street', 'number', 'district',
        'city', 'state', 'postal_code', 'country', 'reference', 'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user() { return $this->belongsTo(User::class); }

    public function toSnapshot(): array
    {
        return $this->only([
            'recipient', 'phone', 'street', 'number', 'district',
            'city', 'state', 'postal_code', 'country', 'reference',
        ]);
    }

    public function fullLine(): string
    {
        return trim("{$this->street} {$this->number}, {$this->district}, {$this->city} ({$this->state}) CP {$this->postal_code}", ' ,');
    }
}
