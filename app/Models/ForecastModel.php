<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForecastModel extends Model
{
    use HasFactory;

    protected $table = 'forecast_models';

    protected $fillable = [
        'name',
        'code',
        'version',
        'parameters',
        'is_active',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(ForecastRun::class, 'model_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ForecastMetric::class, 'model_id');
    }
}
