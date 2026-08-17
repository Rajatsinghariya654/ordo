<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'reason',
        'leave_date',
        'status',
        'reviewed_by',
    ];

    protected $casts = [
        'leave_date' => 'date',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
