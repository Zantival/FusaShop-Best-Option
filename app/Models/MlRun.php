<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlRun extends Model
{
    protected $fillable = [
        'triggered_by',
        'status',
        'parameters',
        'results',
        'summary',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'results' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
