<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'merchant_id', 'name', 'slug', 'description', 'price', 'stock',
        'category', 'type', 'sector_key', 'subsector_key', 'views_count',
        'image', 'available_options', 'images', 'is_active', 'specifications',
        'reserved_stock', 'low_stock_threshold', 'weight_grams', 'length_cm', 'width_cm', 'height_cm', 'is_taxed',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_taxed' => 'boolean',
            'available_options' => 'array',
            'images' => 'array',
            'specifications' => 'array',
        ];
    }

    public function merchant() { return $this->belongsTo(User::class, 'merchant_id')->withTrashed(); }
    public function cartItems() { return $this->hasMany(CartItem::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeSearch($q, $term) {
        return $q->where(function($query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('category', 'like', "%{$term}%");
        });
    }
    public function reviews() { return $this->hasMany(Review::class); }

    public function isService(): bool
    {
        if (($this->type ?? '') === 'service') {
            return true;
        }
        $cat = mb_strtolower($this->category ?? '', 'UTF-8');
        foreach (['servicio', 'glamping', 'turismo', 'hotel', 'reparación', 'clases', 'consultoría', 'transporte', 'evento', 'belleza'] as $kw) {
            if (str_contains($cat, $kw)) {
                return true;
            }
        }
        return false;
    }

    /** Unidades realmente vendibles (stock físico menos reservas de pedidos pendientes). */
    public function availableStock(): int
    {
        return max(0, (int) $this->stock - (int) $this->reserved_stock);
    }

    public function isLowStock(): bool
    {
        return $this->availableStock() <= (int) $this->low_stock_threshold;
    }
}
