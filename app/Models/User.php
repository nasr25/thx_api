<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\CausesActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CausesActivity, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'full_name',
        'full_name_ar',
        'password',
        'department_id',
        'job_title',
        'job_title_ar',
        'profile_photo',
        'preferred_language',
        'is_active',
        'last_login_at',
        'ldap_guid',
        'ldap_domain',
        'monthly_appreciation_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'ldap_guid',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sentAppreciations(): HasMany
    {
        return $this->hasMany(Appreciation::class, 'sender_id');
    }

    public function receivedAppreciations(): HasMany
    {
        return $this->hasMany(Appreciation::class, 'receiver_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
              ->orWhere('username', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('job_title', 'like', "%{$term}%")
              ->orWhere('full_name_ar', 'like', "%{$term}%");
        });
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getProfilePhotoUrlAttribute(): ?string
    {
        // No images in this deployment — the UI renders initials instead.
        // Returns null so the frontend shows the initials avatar fallback.
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return null;
    }

    public function getDisplayNameAttribute(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'ar' && $this->full_name_ar) {
            return $this->full_name_ar;
        }
        return $this->full_name;
    }

    // ─── Methods ──────────────────────────────────────────────────

    public function getMonthlyAppreciationsSentCount(): int
    {
        return $this->sentAppreciations()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function getMonthlyLimit(): int
    {
        if ($this->monthly_appreciation_limit !== null) {
            return $this->monthly_appreciation_limit;
        }
        $setting = Setting::getValue('monthly_appreciation_limit', 10);
        return (int) $setting;
    }

    public function canSendAppreciation(): bool
    {
        return $this->getMonthlyAppreciationsSentCount() < $this->getMonthlyLimit();
    }

    public function getTotalReceivedCount(): int
    {
        return $this->receivedAppreciations()->count();
    }
}
