<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appreciation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_public',
        'deleted_by',
        'deleted_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeForReceiver($query, int $userId)
    {
        return $query->where('receiver_id', $userId);
    }

    public function scopeForSender($query, int $userId)
    {
        return $query->where('sender_id', $userId);
    }

    // ─── Methods ──────────────────────────────────────────────────

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
