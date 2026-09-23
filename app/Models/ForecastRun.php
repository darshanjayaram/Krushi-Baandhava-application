<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastRun extends Model
{
    use HasFactory;

    protected $table = 'forecast_runs';

    protected $fillable = [
        'model_id',
        'started_at',
        'completed_at',
        'status',
        'total_predictions',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_predictions' => 'integer',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(ForecastModel::class, 'model_id');
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(PriceForecast::class, 'run_id');
    }
}
