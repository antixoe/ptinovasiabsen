<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceNotification extends Model
{
    protected $table = 'notifications';
    protected $fillable = ['user_id', 'type', 'title', 'body', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
