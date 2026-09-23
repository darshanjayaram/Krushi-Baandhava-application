<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'variety_id',
        'market_id',
        'district_id',
        'price_date',
        'min_price',
        'max_price',
        'modal_price',
        'arrival_quantity',
        'unit',
        'data_source_id',
        'raw_record_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketPrice $price) {
            if (! $price->district_id && $price->market_id) {
                $price->district_id = Market::find($price->market_id)?->district_id;
            }
            if (! $price->data_source_id) {
                $price->data_source_id = DataSource::first()?->id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price_date' => 'date',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'modal_price' => 'decimal:2',
            'arrival_quantity' => 'decimal:2',
        ];
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function rawRecord(): BelongsTo
    {
        return $this->belongsTo(MarketPriceRaw::class, 'raw_record_id');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('price_date', Carbon::today());
    }

    public function scopeLatestAvailable(Builder $query): Builder
    {
        return $query->orderByDesc('price_date');
    }

    public function scopeForCrop(Builder $query, int $cropId): Builder
    {
        return $query->where('crop_id', $cropId);
    }

    public function scopeForMarket(Builder $query, int $marketId): Builder
    {
        return $query->where('market_id', $marketId);
    }

    public function scopeForDistrict(Builder $query, int $districtId): Builder
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('price_date', [$startDate, $endDate]);
    }

    public function getPriceSpreadAttribute(): float
    {
        return (float) ($this->max_price - $this->min_price);
    }

    /**
     * Scope query to only include prices for markets located in Karnataka.
     */
    public function scopeKarnataka(Builder $query): Builder
    {
        return $query->whereHas('market.district.state', function ($s) {
            $s->where('code', 'KA')->orWhere('name', 'Karnataka');
        });
    }
}
