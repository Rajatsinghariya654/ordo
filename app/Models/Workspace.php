<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'invite_code',
    ];

    // Model banate waqt automatically slug + invite_code generate karo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = Str::slug($workspace->name) . '-' . Str::random(5);
            }
            if (empty($workspace->invite_code)) {
                $workspace->invite_code = Str::upper(Str::random(8));
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function joinRequests()
    {
        return $this->hasMany(WorkspaceJoinRequest::class);
    }

    public function pendingJoinRequests()
    {
        return $this->hasMany(WorkspaceJoinRequest::class)->where('status', 'pending');
    }

    public function leaveRequests()
    {
        return $this->hasMany(WorkspaceLeaveRequest::class);
    }

    public function pendingLeaveRequests()
    {
        return $this->hasMany(WorkspaceLeaveRequest::class)->where('status', 'pending');
    }

    // ─── Permission Helpers ──────────────────────────────────────────

    public function userRole($user)
    {
        $userId = is_numeric($user) ? $user : $user->id;
        if ($this->owner_id == $userId) {
            return 'owner';
        }
        $member = $this->members()->where('users.id', $userId)->first();
        return $member ? $member->pivot->role : null;
    }

    public function isOwner($user)
    {
        $userId = is_numeric($user) ? $user : $user->id;
        return $this->owner_id == $userId;
    }

    public function isManager($user)
    {
        $role = $this->userRole($user);
        return $role === 'manager';
    }

    public function canManageMembers($user)
    {
        $role = $this->userRole($user);
        return in_array($role, ['owner', 'manager']);
    }
}