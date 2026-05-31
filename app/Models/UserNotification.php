<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'title_ar',
        'body',
        'body_ar',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getDisplayTitleAttribute(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'ar' && $this->title_ar) {
            return $this->title_ar;
        }
        return $this->title;
    }

    public function getDisplayBodyAttribute(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'ar' && $this->body_ar) {
            return $this->body_ar;
        }
        return $this->body;
    }
}
