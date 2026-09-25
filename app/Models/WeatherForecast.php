<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'forecast_date',
        'latitude',
        'longitude',
        'current_temperature',
        'current_humidity',
        'current_wind_speed',
        'current_weather_code',
        'temp_min',
        'temp_max',
        'precipitation_probability',
        'weather_code',
        'weather_condition_en',
        'weather_condition_kn',
        'weather_icon',
        'farming_advisory_en',
        'farming_advisory_kn',
        'raw_payload',
        'fetched_at',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'fetched_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'current_temperature' => 'float',
        'current_humidity' => 'float',
        'current_wind_speed' => 'float',
        'current_weather_code' => 'integer',
        'temp_min' => 'float',
        'temp_max' => 'float',
        'precipitation_probability' => 'float',
        'weather_code' => 'integer',
        'raw_payload' => 'array',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function scopeForDistrict(Builder $query, int $districtId): Builder
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('forecast_date', '>=', Carbon::today()->toDateString())
            ->orderBy('forecast_date', 'asc');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('forecast_date', Carbon::today()->toDateString());
    }

    /**
     * Get day of week in Kannada.
     */
    public function getDayNameKnAttribute(): string
    {
        $kannadaDays = [
            0 => 'ಭಾನುವಾರ',
            1 => 'ಸೋಮವಾರ',
            2 => 'ಮಂಗಳವಾರ',
            3 => 'ಬುಧವಾರ',
            4 => 'ಗುರುವಾರ',
            5 => 'ಶುಕ್ರವಾರ',
            6 => 'ಶನಿವಾರ',
        ];

        return $kannadaDays[$this->forecast_date->dayOfWeek] ?? '';
    }

    /**
     * Get day of week in English.
     */
    public function getDayNameEnAttribute(): string
    {
        return $this->forecast_date->format('l');
    }

    /**
     * Determine if forecast is for today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->forecast_date->isToday();
    }

    /**
     * Get localized weather condition label.
     */
    public function conditionLabel(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'en') {
            return $this->weather_condition_en ?: 'Partly Cloudy';
        }
        return $this->weather_condition_kn ?: 'ಭಾಗಶಃ ಮೋಡ';
    }

    /**
     * Get localized agricultural advisory label.
     */
    public function advisoryLabel(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'en') {
            return $this->farming_advisory_en ?: 'Good day for drying and logistics';
        }
        return $this->farming_advisory_kn ?: 'ಒಣಗಿಸಲು ಒಳ್ಳೆಯ ದಿನ — ಸಾಗಾಣಿಕೆಗೆ ಸೂಕ್ತ';
    }

    /**
     * Get temperature to display (current or daytime average/max).
     */
    public function displayTemperature(): int
    {
        $temp = $this->current_temperature ?? $this->temp_max ?? (($this->temp_min + $this->temp_max) / 2);
        return (int) round($temp ?: 24);
    }
}
