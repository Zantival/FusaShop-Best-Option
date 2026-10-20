<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'merchant_id',
        'company_name',
        'phone',
        'rut_path',
        'camara_comercio_path',
        'logo_path',
        'banners_path',
        'description',
        'address',
        'google_maps_url',
        'latitude',
        'longitude',
        'whatsapp',
        'kyc_status',
        'kyc_rejection_code',
        'kyc_notes',
        'business_type',
        'sector_override',
        'subsector_override',
        'settlement_status',
        'settled_at',
        'employee_count',
    ];

    protected function casts(): array
    {
        return [
            'banners_path' => 'array',
            'settled_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id')->withTrashed();
    }

    public function logoUrl(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }
        return asset('storage/' . ltrim($this->logo_path, '/'));
    }
}
