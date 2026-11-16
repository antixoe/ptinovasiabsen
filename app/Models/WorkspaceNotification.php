<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'link', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function icon(): string
    {
        return match ($this->type) {
            'report.submitted' => 'inbox',
            'report.reviewed' => 'check',
            'report.revision' => 'alert',
            'agenda.created', 'agenda.updated' => 'calendar',
            'report.reminder' => 'bell',
            default => 'bell',
        };
    }
}
