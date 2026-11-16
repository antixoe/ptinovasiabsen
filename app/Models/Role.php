<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const PERMISSIONS = [
        'manage-users' => 'Kelola anggota & akun',
        'manage-roles' => 'Kelola peran & hak akses',
        'view-audit' => 'Lihat audit log',
        'review-reports' => 'Review laporan harian',
        'view-all-reports' => 'Lihat laporan semua anggota',
        'view-all-agendas' => 'Lihat agenda semua anggota',
        'manage-all-agendas' => 'Kelola agenda semua anggota',
    ];

    protected $fillable = ['name', 'slug', 'description', 'is_system', 'permissions'];

    protected $casts = [
        'is_system' => 'boolean',
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
