<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalBanner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'image_path', 'link_url', 'is_active', 'sort_order',
        'starts_at', 'ends_at', 'impressions_count', 'clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return '';
        }
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }
        return asset('storage/' . $this->image_path);
    }

    public function getIsVideoAttribute(): bool
    {
        $ext = strtolower(pathinfo($this->image_path ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'mov']);
    }

    public function getCtrAttribute(): float
    {
        if ($this->impressions_count <= 0) {
            return 0.0;
        }
        return round(($this->clicks_count / $this->impressions_count) * 100, 2);
    }

    public function isCurrentlyScheduled(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        return true;
    }
}
