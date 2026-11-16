<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    public const STATUS_LABELS = [
        'draft' => 'Belum diisi',
        'morning' => 'Agenda pagi terkirim',
        'summary' => 'Menunggu review',
        'reviewed' => 'Sudah direview',
        'revision' => 'Perlu revisi',
    ];

    protected $fillable = [
        'user_id', 'report_date', 'status', 'summary_note',
        'morning_submitted_at', 'summary_submitted_at',
        'reviewed_at', 'reviewed_by', 'review_note',
    ];

    protected $casts = [
        'report_date' => 'date',
        'morning_submitted_at' => 'datetime',
        'summary_submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canViewAllReports()) {
            return $query;
        }

        if ($user->isMentor()) {
            return $query->whereIn('user_id', $user->mentees()->select('id'));
        }

        return $query->where('user_id', $user->id);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'reviewed' => 'success',
            'summary' => 'warning',
            'revision' => 'danger',
            'morning' => 'info',
            default => 'neutral',
        };
    }

    public function isMorningSubmitted(): bool
    {
        return $this->morning_submitted_at !== null;
    }

    public function isSummarySubmitted(): bool
    {
        return $this->summary_submitted_at !== null;
    }
}
