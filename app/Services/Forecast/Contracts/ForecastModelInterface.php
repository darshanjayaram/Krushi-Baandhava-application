<?php

namespace App\Services\Forecast\Contracts;

interface ForecastModelInterface
{
    /**
     * Get model human-readable name.
     */
    public function getName(): string;

    /**
     * Get model unique code identifier.
     */
    public function getCode(): string;

    /**
     * Compute projection for a given historical timeseries and horizon.
     *
     * @param  array<int, float>  $historicalPrices  Chronological array of prices (oldest to newest)
     * @param  int  $horizonDays  Target horizon in days (1, 7, 15, 30)
     * @return array{expected_price: float, lower_bound: float, upper_bound: float, confidence_score: float, rmse: float, mae: float}
     */
    public function forecast(array $historicalPrices, int $horizonDays): array;
}
