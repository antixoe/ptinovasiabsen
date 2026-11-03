<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agenda extends Model
{
    protected $fillable = [
        'user_id', 'reviewer_id', 'title', 'description', 'agenda_date', 'start_time', 'end_time',
        'status', 'summary', 'morning_submitted_at', 'summary_submitted_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'agenda_date' => 'date', 'morning_submitted_at' => 'datetime', 'summary_submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
