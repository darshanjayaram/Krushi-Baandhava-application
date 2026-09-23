<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'provider_class',
        'type',
        'base_url',
        'endpoint',
        'auth_type',
        'sync_frequency',
        'is_active',
        'timeout_seconds',
        'rate_limit_per_minute',
        'last_sync_at',
        'last_sync_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'timeout_seconds' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function credential(): HasOne
    {
        return $this->hasOne(DataSourceCredential::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(DataSourceMapping::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(ApiHealthLog::class);
    }

    public function cropMappings(): HasMany
    {
        return $this->hasMany(CropSourceMapping::class);
    }

    public function marketMappings(): HasMany
    {
        return $this->hasMany(MarketSourceMapping::class);
    }
}
