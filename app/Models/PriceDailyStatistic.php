<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceDailyStatistic extends Model
{
    use HasFactory;

    protected $table = 'price_daily_statistics';

    protected $fillable = [
        'crop_id',
        'variety_id',
        'state_id',
        'record_date',
        'avg_modal_price',
        'min_modal_price',
        'max_modal_price',
        'total_arrival_quantity',
        'active_markets_count',
    ];

    protected $casts = [
        'record_date' => 'date',
        'avg_modal_price' => 'decimal:2',
        'min_modal_price' => 'decimal:2',
        'max_modal_price' => 'decimal:2',
        'total_arrival_quantity' => 'decimal:2',
        'active_markets_count' => 'integer',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(CropVariety::class, 'variety_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function scopeForCrop(Builder $query, int $cropId, ?int $varietyId = null): Builder
    {
        $query->where('crop_id', $cropId);
        if ($varietyId !== null) {
            $query->where('variety_id', $varietyId);
        }
        return $query;
    }

    public function scopeForRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }
}
