<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppreciationReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function appreciations(): HasMany
    {
        return $this->hasMany(Appreciation::class, 'reason_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }
}
