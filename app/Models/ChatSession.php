<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'openai_thread_id',
        'title',
        'module_filter',
        'user_id',
        'session_type',
        'chat_type',
        'desk365_ticket_id',
        'participant_ids',
    ];

    protected function casts(): array
    {
        return [
            'participant_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_session_favorites')->withTimestamps();
    }
}
