<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastMetric extends Model
{
    use HasFactory;

    protected $table = 'forecast_metrics';

    protected $fillable = [
        'model_id',
        'crop_id',
        'variety_id',
        'market_id',
        'horizon_days',
        'mae',
        'rmse',
        'mape',
        'directional_accuracy',
    ];

    protected $casts = [
        'horizon_days' => 'integer',
        'mae' => 'decimal:2',
        'rmse' => 'decimal:2',
        'mape' => 'decimal:2',
        'directional_accuracy' => 'decimal:2',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(ForecastModel::class, 'model_id');
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
}
