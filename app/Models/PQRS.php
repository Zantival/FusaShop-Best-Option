<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PQRS extends Model
{
    protected $table = 'p_q_r_s';

    protected $fillable = [
        'user_id',
        'type',
        'category',
        'priority',
        'subject',
        'content',
        'status',
        'admin_response',
        'resolved_at',
        'due_at',
        'assigned_to',
        'merchant_id',
        'conversation_history',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at'          => 'datetime',
            'due_at'               => 'datetime',
            'conversation_history' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id')->withTrashed();
    }

    /**
     * Calcula el estado de SLA según Ley 1755 de 2015 (15 días hábiles por defecto).
     */
    public function slaInfo(): array
    {
        if (in_array($this->status, ['resolved', 'closed'], true)) {
            return [
                'status' => 'cumplido',
                'label'  => 'Resuelto',
                'class'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            ];
        }

        $due = $this->due_at ?? $this->created_at?->copy()->addWeekdays(Setting::pqrsSlaBusinessDays());
        if (!$due) {
            return [
                'status' => 'en_tiempo',
                'label'  => 'En tiempo',
                'class'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        }

        $daysLeft = (int) round(now()->diffInHours($due, false) / 24);
        if ($daysLeft < 0) {
            return [
                'status' => 'vencido',
                'label'  => 'Vencido (' . abs($daysLeft) . 'd)',
                'class'  => 'bg-rose-100 text-rose-800 border-rose-300 animate-pulse',
            ];
        }
        if ($daysLeft <= 3) {
            return [
                'status' => 'por_vencer',
                'label'  => 'Vence en ' . $daysLeft . 'd',
                'class'  => 'bg-amber-100 text-amber-800 border-amber-300',
            ];
        }

        return [
            'status' => 'en_tiempo',
            'label'  => $daysLeft . 'd restantes',
            'class'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ];
    }
}
