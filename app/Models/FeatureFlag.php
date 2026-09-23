<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Check if a feature flag is enabled, utilizing cache for performance.
     */
    public static function isEnabled(string $key, bool $default = true): bool
    {
        return Cache::remember("feature_flag_{$key}", 300, function () use ($key, $default) {
            $flag = static::where('key', $key)->first();
            return $flag ? (bool) $flag->is_enabled : $default;
        });
    }
}
