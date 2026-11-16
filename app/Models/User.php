<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'level_id',
        'mentor_id',
        'department',
        'phone',
        'avatar_color',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentees(): HasMany
    {
        return $this->hasMany(User::class, 'mentor_id');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function reviewedAgendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'reviewed_by');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function workspaceNotifications(): HasMany
    {
        return $this->hasMany(WorkspaceNotification::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role?->slug === 'superadmin';
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isSuperadmin() || (bool) $this->role?->hasPermission($permission);
    }

    public function isStudent(): bool
    {
        return $this->level?->slug === 'students';
    }

    public function isMentor(): bool
    {
        return $this->level?->slug === 'pembimbing';
    }

    public function canReviewReports(): bool
    {
        return $this->hasPermission('review-reports');
    }

    public function canViewAllAgendas(): bool
    {
        return $this->hasPermission('view-all-agendas');
    }

    public function canManageAllAgendas(): bool
    {
        return $this->hasPermission('manage-all-agendas');
    }

    public function canViewAllReports(): bool
    {
        return $this->hasPermission('view-all-reports');
    }

    /**
     * Can this user view and manage the given member's work?
     */
    public function oversees(User $member): bool
    {
        if ($this->id === $member->id) {
            return true;
        }

        return $this->canViewAllAgendas() || ($this->isMentor() && $member->mentor_id === $this->id);
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

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(substr($parts[0] ?? '?', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    public function avatarColor(): string
    {
        return $this->avatar_color ?: 'violet';
    }

    public function unreadNotificationCount(): int
    {
        return $this->workspaceNotifications()->whereNull('read_at')->count();
    }
}
