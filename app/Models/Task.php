<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'group_id',
        'creator_id',
        'assignee_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'started_at',
        'completed_at',
        'latitude',
        'longitude',
        'location_name',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function subtasks()
    {
        return $this->hasMany(TaskSubtask::class)->orderBy('order');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function group()
    {
        return $this->belongsTo(TaskGroup::class, 'group_id');
    }
    // ─── Query Scopes (Week 1 style — reusable filters) ────────────

    public function scopePersonal($query)
    {
        return $query->whereNull('workspace_id');
    }

    public function scopeInWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'completed';
    }
}
