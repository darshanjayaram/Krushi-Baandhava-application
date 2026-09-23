<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceForecast extends Model
{
    use HasFactory;

    protected $table = 'price_forecasts';

    protected $fillable = [
        'run_id',
        'crop_id',
        'variety_id',
        'market_id',
        'forecast_date',
        'horizon_days',
        'expected_price',
        'lower_bound',
        'upper_bound',
        'confidence_score',
        'data_points_used',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'horizon_days' => 'integer',
        'expected_price' => 'decimal:2',
        'lower_bound' => 'decimal:2',
        'upper_bound' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'data_points_used' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ForecastRun::class, 'run_id');
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(CropVariety::class, 'variety_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function scopeForCrop(Builder $query, int $cropId, ?int $varietyId = null): Builder
    {
        $query->where('crop_id', $cropId);
        if ($varietyId !== null) {
            $query->where('variety_id', $varietyId);
        }
        return $query;
    }

    public function scopeForMarket(Builder $query, ?int $marketId = null): Builder
    {
        if ($marketId !== null) {
            return $query->where('market_id', $marketId);
        }
        return $query->whereNull('market_id');
    }

    public function scopeForHorizon(Builder $query, int $days): Builder
    {
        return $query->where('horizon_days', $days);
    }

    /**
     * Kannada horizon label.
     */
    public function getHorizonLabelKnAttribute(): string
    {
        return match ($this->horizon_days) {
            1 => 'ನಾಳೆ (+1 ದಿನ)',
            7 => 'ಮುಂದಿನ 7 ದಿನಗಳು (+7 ದಿನ)',
            15 => 'ಮುಂದಿನ 15 ದಿನಗಳು (+15 ದಿನ)',
            30 => 'ಮುಂದಿನ 30 ದಿನಗಳು (+30 ದಿನ)',
            default => "+{$this->horizon_days} ದಿನಗಳು",
        };
    }

    /**
     * English horizon label.
     */
    public function getHorizonLabelEnAttribute(): string
    {
        return match ($this->horizon_days) {
            1 => 'Tomorrow (+1 Day)',
            7 => 'Next 7 Days',
            15 => 'Next 15 Days',
            30 => 'Next 30 Days',
            default => "+{$this->horizon_days} Days",
        };
    }

    /**
     * Price spread between upper and lower bounds.
     */
    public function getBoundSpreadAttribute(): float
    {
        return round((float) $this->upper_bound - (float) $this->lower_bound, 2);
    }
}
