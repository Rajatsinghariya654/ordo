<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prompt_text',
        'ai_response_json',
        'tokens_used',
        'execution_time_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ai_response_json' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}