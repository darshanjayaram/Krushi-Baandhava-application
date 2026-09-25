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
        'sync_time',
        'sync_days',
        'cron_expression',
        'is_active',
        'timeout_seconds',
        'rate_limit_per_minute',
        'last_sync_at',
        'last_sync_status',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'timeout_seconds' => 'integer',
            'rate_limit_per_minute' => 'integer',
            'last_sync_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
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

    /**
     * Determine if this data source is due for synchronization based on its schedule.
     */
    public function isDue(?\Carbon\Carbon $now = null): bool
    {
        $now = $now ?? \Carbon\Carbon::now();

        // 1. Operating Days Check
        if ($this->sync_days === 'mon_sat' && $now->isSunday()) {
            return false;
        }

        // 2. Frequency Check
        switch ($this->sync_frequency) {
            case 'hourly':
                return $this->last_sync_at === null || $this->last_sync_at->diffInMinutes($now) >= 55;

            case 'every_2_hours':
                return $this->last_sync_at === null || $this->last_sync_at->diffInMinutes($now) >= 115;

            case 'every_6_hours':
                return $this->last_sync_at === null || $this->last_sync_at->diffInHours($now) >= 5.5;

            case 'every_12_hours':
                return $this->last_sync_at === null || $this->last_sync_at->diffInHours($now) >= 11.5;

            case 'weekly':
                return $this->last_sync_at === null || $this->last_sync_at->diffInDays($now) >= 6;

            case 'twice_daily':
            case 'daily':
            default:
                if (!empty($this->sync_time)) {
                    $targetTimes = array_map('trim', explode(',', $this->sync_time));
                    $currentHm = $now->format('H:i');
                    foreach ($targetTimes as $time) {
                        try {
                            $targetCarbon = \Carbon\Carbon::createFromTimeString($time);
                            if ($targetCarbon && abs($now->diffInMinutes($targetCarbon)) <= 45) {
                                if ($this->last_sync_at === null || $this->last_sync_at->diffInMinutes($now) >= 50) {
                                    return true;
                                }
                            }
                        } catch (\Throwable $e) {
                            // ignore invalid string format fallback
                        }
                    }
                }
                return $this->last_sync_at === null || $this->last_sync_at->diffInHours($now) >= 10;
        }
    }
}

