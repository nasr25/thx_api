<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    // ─── Static Helpers ───────────────────────────────────────────

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->getCastedValue() : $default;
        });
    }

    public static function setValue(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );
        Cache::forget("setting_{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastedValue()];
        })->toArray();
    }

    public static function getAllSettings(): array
    {
        return static::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastedValue()];
        })->toArray();
    }

    public function getCastedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($this->value, true),
            'float'   => (float) $this->value,
            default   => $this->value,
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });
    }
}
