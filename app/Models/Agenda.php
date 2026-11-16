<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agenda extends Model
{
    protected $fillable = [
        'user_id', 'daily_report_id', 'reviewer_id', 'title', 'description', 'agenda_date', 'sort_order',
        'start_time', 'end_time', 'priority', 'status', 'summary',
        'morning_submitted_at', 'summary_submitted_at', 'completed_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'agenda_date' => 'date',
        'morning_submitted_at' => 'datetime',
        'summary_submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canViewAllAgendas()) {
            return $query;
        }

        if ($user->isMentor()) {
            return $query->where(function (Builder $inner) use ($user) {
                $inner->where('user_id', $user->id)
                    ->orWhereIn('user_id', $user->mentees()->select('id'));
            });
        }

        return $query->where('user_id', $user->id);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'completed', 'reviewed' => 'Selesai',
            'in_progress' => 'Berjalan',
            'cancelled' => 'Dibatalkan',
            default => 'Terjadwal',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'completed', 'reviewed' => 'success',
            'in_progress' => 'info',
            'cancelled' => 'neutral',
            default => 'warning',
        };
    }

    public function isDone(): bool
    {
        return in_array($this->status, ['completed', 'reviewed'], true);
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'high' => 'Tinggi',
            'low' => 'Rendah',
            default => 'Sedang',
        };
    }
}
