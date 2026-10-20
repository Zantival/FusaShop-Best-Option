<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerRequest extends Model
{
    protected $fillable = [
        'user_id', 'image_path', 'title', 'payment_proof_path', 'status', 'cost', 'notes', 'rejection_reason',
        'target_type', 'selected_products', 'target_url',
    ];

    protected $casts = [
        'selected_products' => 'array',
        'cost' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
