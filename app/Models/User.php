<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
        'is_admin',
        'admin_granted_by',
        'is_suspended',
        'is_blocked',
        'status',
        'bio',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_suspended' => 'boolean',
            'is_blocked' => 'boolean',
        ];
    }

    // ─── RBAC Helper Methods (Week 3 style — no Spatie) ───────────────

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function isPersonal(): bool
    {
        return $this->account_type === 'personal';
    }

    public function isBusiness(): bool
    {
        return $this->account_type === 'business';
    }

    public function isActive(): bool
    {
        return ! $this->is_suspended && ! $this->is_blocked;
    }

    // Check if this user can be moderated by the given admin user
    // Rules:
    // 1. Pre-existing admins (no recorded sponsor) — nobody through this new rule can moderate them
    // 2. Admins can only moderate admins they personally granted access to
    // 3. Non-admin users — any admin can moderate them (existing behavior)

    public function canBeModeratedBy(User $admin): bool
    {
        // Pre-existing admins (no recorded sponsor) — nobody through this new rule can moderate them
        if ($this->isAdmin() && is_null($this->admin_granted_by)) {
            return false;
        }

        // Admins can only moderate admins they personally granted access to
        if ($this->isAdmin()) {
            return $this->admin_granted_by === $admin->id;
        }

        // Non-admin users — any admin can moderate them (existing behavior)
        return true;
    }

    // ─── Relationships ──────────────────────────────────────────────

    // Workspaces jinka ye user member hai (many-to-many via workspace_user)
    public function workspaces()
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Workspaces jinka ye user owner hai
    public function ownedWorkspaces()
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    // Tasks jo isne banaye
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    // Tasks jo isko assign hue
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    // AI logs
    public function aiLogs()
    {
        return $this->hasMany(AiLog::class);
    }

    // Account switch requests jo isne bheje
    public function switchRequests()
    {
        return $this->hasMany(AccountSwitchRequest::class);
    }


    // Admin granted by relationship
    public function adminGrantedBy()
    {
        return $this->belongsTo(User::class, 'admin_granted_by');
    }

    //workspace join requests jo isne bheje

    public function workspaceJoinRequests()
    {
        return $this->hasMany(WorkspaceJoinRequest::class);
    }

    public function workspaceLeaveRequests()
    {
        return $this->hasMany(WorkspaceLeaveRequest::class);
    }

    public function isInWorkspace($workspaceId): bool
    {
        return $this->workspaces()->where('workspaces.id', $workspaceId)->exists();
    }

    public function workspaceRole($workspaceId): ?string
    {
        $ws = $this->workspaces()->where('workspaces.id', $workspaceId)->first();
        return $ws ? $ws->pivot->role : null;
    }
}
