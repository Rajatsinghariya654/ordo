<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_user_id',
        'action_by',
        'workspace_id',
        'action_type',
        'reason',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}