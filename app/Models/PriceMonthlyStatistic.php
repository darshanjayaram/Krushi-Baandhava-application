<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceMonthlyStatistic extends Model
{
    use HasFactory;

    protected $table = 'price_monthly_statistics';

    protected $fillable = [
        'crop_id',
        'variety_id',
        'market_id',
        'year',
        'month',
        'avg_modal_price',
        'min_price',
        'max_price',
        'seasonal_index',
        'observations_count',
        'total_arrivals',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'avg_modal_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'seasonal_index' => 'decimal:4',
        'observations_count' => 'integer',
        'total_arrivals' => 'decimal:2',
    ];

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

    /**
     * Get English Month Name.
     */
    public function getMonthNameEnAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->format('F');
    }

    /**
     * Get Kannada Month Name.
     */
    public function getMonthNameKnAttribute(): string
    {
        $kannadaMonths = [
            1 => 'ಜನವರಿ',
            2 => 'ಫೆಬ್ರವರಿ',
            3 => 'ಮಾರ್ಚ್',
            4 => 'ಏಪ್ರಿಲ್',
            5 => 'ಮೇ',
            6 => 'ಜೂನ್',
            7 => 'ಜುಲೈ',
            8 => 'ಆಗಸ್ಟ್',
            9 => 'ಸೆಪ್ಟೆಂಬರ್',
            10 => 'ಅಕ್ಟೋಬರ್',
            11 => 'ನವೆಂಬರ್',
            12 => 'ಡಿಸೆಂಬರ್',
        ];

        return $kannadaMonths[$this->month] ?? $this->month_name_en;
    }

    /**
     * Classify selling window based on seasonal index.
     * Peak: > 1.05
     * Average: 0.95 - 1.05
     * Lean: < 0.95
     */
    public function getSeasonalityLabelAttribute(): array
    {
        $index = (float) ($this->seasonal_index ?? 1.0);

        if ($index >= 1.08) {
            return [
                'type' => 'peak',
                'badge' => 'ಅತ್ಯುತ್ತಮ ಮಾರಾಟ ಸಮಯ (Peak Price Window)',
                'badge_en' => 'Peak Price Window',
                'color' => 'emerald',
                'advice_kn' => 'ಈ ತಿಂಗಳಲ್ಲಿ ಬೆಲೆ ಗರಿಷ್ಠ ಮಟ್ಟದಲ್ಲಿರುತ್ತದೆ. ದಾಸ್ತಾನು ಮಾಡಿದ ಬೆಳೆಯನ್ನು ಉತ್ತಮ ಲಾಭಕ್ಕೆ ಮಾರಾಟ ಮಾಡಲು ಸೂಕ್ತ ಕಾಲ.',
            ];
        }

        if ($index >= 1.02) {
            return [
                'type' => 'above_average',
                'badge' => 'ಉತ್ತಮ ಬೆಲೆ (Above Average)',
                'badge_en' => 'Above Average',
                'color' => 'blue',
                'advice_kn' => 'ಸಾಧಾರಣಕ್ಕಿಂತ ಹೆಚ್ಚಿನ ಧಾರಣೆ ಇರುತ್ತದೆ. ಮಾರುಕಟ್ಟೆ ಆವಕ ಗಮನಿಸಿ ಮಾರಾಟ ಮಾಡಬಹುದು.',
            ];
        }

        if ($index >= 0.95) {
            return [
                'type' => 'average',
                'badge' => 'ಸಾಧಾರಣ ಬೆಲೆ (Average Price)',
                'badge_en' => 'Average Price',
                'color' => 'amber',
                'advice_kn' => 'ಸ್ಥಿರ ಮತ್ತು ಸಾಮಾನ್ಯ ಧಾರಣೆ ಇರುತ್ತದೆ.',
            ];
        }

        return [
            'type' => 'lean',
            'badge' => 'ಕಡಿಮೆ ಧಾರಣೆ / ಆವಕ ಹೆಚ್ಚಳ (Lean Window)',
            'badge_en' => 'Lean / Harvest Flush',
            'color' => 'rose',
            'advice_kn' => 'ಹೊಸ ಫಸಲು ಹೆಚ್ಚಿನ ಪ್ರಮಾಣದಲ್ಲಿ ಮಾರುಕಟ್ಟೆಗೆ ಬರುವುದರಿಂದ ಬೆಲೆ ಕುಸಿಯುವ ಸಾಧ್ಯತೆ ಇರುತ್ತದೆ. ಸಾಧ್ಯವಾದರೆ ದಾಸ್ತಾನು ಮಾಡಿ.',
        ];
    }
}
