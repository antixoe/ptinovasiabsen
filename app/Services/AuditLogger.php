<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an auditable action for the given entity.
     */
    public static function log(string $action, ?Model $entity = null, array $metadata = [], ?User $actor = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => ($actor ?? Auth::user())?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
