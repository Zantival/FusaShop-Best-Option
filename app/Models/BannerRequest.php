<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerRequest extends Model
{
    protected $fillable = [
        'user_id', 'image_path', 'payment_proof_path', 'status', 'cost', 'notes', 'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
