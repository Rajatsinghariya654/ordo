<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskGroup extends Model
{
    use HasFactory;

    protected $fillable = ['creator_id', 'title', 'color', 'is_recurring', 'recurrence_template'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'group_id');
    }

    protected function casts(): array
    {
        return [
            'recurrence_template' => 'array',
            'is_recurring' => 'boolean',
        ];
    }
}
