<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalAppreciationsAttribute(): int
    {
        return Appreciation::whereHas('receiver', function ($q) {
            $q->where('department_id', $this->id);
        })->count();
    }
}
