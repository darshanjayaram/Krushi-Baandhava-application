<?php

namespace App\Services\Forecast\Models;

use App\Services\Forecast\Contracts\ForecastModelInterface;
use Carbon\Carbon;

class SeasonalDecompositionModel implements ForecastModelInterface
{
    /**
     * Array of monthly seasonal indices keyed by month number (1 - 12).
     *
     * @var array<int, float>
     */
    protected array $seasonalIndices;

    public function __construct(array $seasonalIndices = [])
    {
        $this->seasonalIndices = $seasonalIndices;
    }

    public function getName(): string
    {
        return 'Seasonal Decomposition & Index';
    }

    public function getCode(): string
    {
        return 'seasonal_decomposition';
    }

    public function forecast(array $historicalPrices, int $horizonDays): array
    {
        $n = count($historicalPrices);
        if ($n === 0) {
            return [
                'expected_price' => 0.0,
                'lower_bound' => 0.0,
                'upper_bound' => 0.0,
                'confidence_score' => 20.0,
                'rmse' => 0.0,
                'mae' => 0.0,
            ];
        }

        // Baseline: Exponentially weighted moving average of the last 14 days
        $window = array_slice($historicalPrices, max(0, $n - 14));
        $weights = [];
        $weightSum = 0;
        $weightedPriceSum = 0;

        foreach ($window as $idx => $price) {
            $w = exp(0.15 * $idx);
            $weights[] = $w;
            $weightSum += $w;
            $weightedPriceSum += ($price * $w);
        }

        $baseline = $weightSum > 0 ? ($weightedPriceSum / $weightSum) : (array_sum($window) / count($window));

        // Determine target future date month
        $targetMonth = (int) Carbon::today()->addDays($horizonDays)->month;
        $seasonalMultiplier = (float) ($this->seasonalIndices[$targetMonth] ?? 1.0);

        // Projected expected price
        $expectedPrice = round($baseline * $seasonalMultiplier, 2);

        // Variance estimation
        $variance = 0.0;
        foreach ($window as $p) {
            $variance += pow($p - $baseline, 2);
        }
        $stdDev = count($window) > 1 ? sqrt($variance / (count($window) - 1)) : ($baseline * 0.08);

        $horizonScaling = sqrt(($horizonDays / 7.0) + 0.5);
        $margin = 1.96 * $stdDev * $horizonScaling;

        $lowerBound = max(0.0, round($expectedPrice - $margin, 2));
        $upperBound = round($expectedPrice + $margin, 2);

        $cv = $baseline > 0 ? ($stdDev / $baseline) : 0.5;
        $confidenceScore = round(max(20.0, min(95.0, (1.0 - $cv) * 90.0)), 1);

        return [
            'expected_price' => $expectedPrice,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
            'confidence_score' => $confidenceScore,
            'rmse' => round($stdDev, 2),
            'mae' => round($stdDev * 0.8, 2),
        ];
    }
}
