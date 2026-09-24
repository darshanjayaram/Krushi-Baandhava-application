<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'taluk_id',
        'name',
        'name_kn',
        'code',
        'market_type',
        'latitude',
        'longitude',
        'address',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function taluk(): BelongsTo
    {
        return $this->belongsTo(Taluk::class);
    }

    public function prices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MarketPrice::class)->orderBy('price_date', 'desc');
    }

    public function sourceMappings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MarketSourceMapping::class);
    }

    /**
     * Scope query to only include markets located in Karnataka.
     */
    public function scopeKarnataka(Builder $query): Builder
    {
        return $query->whereHas('district.state', function ($s) {
            $s->where('code', 'KA')->orWhere('name', 'Karnataka');
        });
    }

    /**
     * Scope query to find markets within a radius (km) using Haversine distance.
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 50): Builder
    {
        // Earth radius ~ 6371 km
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("{$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }
}
