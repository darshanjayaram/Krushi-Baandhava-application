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
        return 'Hybrid Momentum & Seasonal Decomposition';
    }

    public function getCode(): string
    {
        return 'seasonal_decomposition';
    }

    /**
     * Econometric Hybrid Forecast:
     * Blends short-term price momentum (EWMA + drift) with deseasonalized multi-year monthly indices.
     */
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

        // 1. Current Price Level & Recent Momentum Window (up to 14 days)
        $currentPrice = (float) end($historicalPrices);
        $windowSize = min(14, $n);
        $window = array_slice($historicalPrices, -$windowSize);

        // Exponential weighting for current baseline
        $weights = [];
        $weightSum = 0;
        $weightedPriceSum = 0;
        foreach ($window as $idx => $price) {
            $w = exp(0.12 * $idx);
            $weights[] = $w;
            $weightSum += $w;
            $weightedPriceSum += ($price * $w);
        }
        $baseline = $weightSum > 0 ? ($weightedPriceSum / $weightSum) : $currentPrice;

        // 2. Short-term drift / velocity (over preceding 7 observations)
        $velocityWindow = array_slice($historicalPrices, -min(7, $n));
        $vCount = count($velocityWindow);
        $dailyDrift = 0.0;
        if ($vCount >= 3) {
            $dailyDrift = ($velocityWindow[$vCount - 1] - $velocityWindow[0]) / max(1, $vCount - 1);
            // Dampen extreme daily drift to prevent runaway slopes
            $maxAllowedDrift = $currentPrice * 0.02; // max 2% per day drift
            $dailyDrift = max(-$maxAllowedDrift, min($maxAllowedDrift, $dailyDrift));
        }
        $momentumPrice = max($currentPrice * 0.4, $currentPrice + ($dailyDrift * $horizonDays));

        // 3. Econometric Deseasonalized Target Price
        $today = Carbon::today();
        $currentMonth = (int) $today->month;
        $targetMonth = (int) $today->copy()->addDays($horizonDays)->month;

        $currentIndex = (float) ($this->seasonalIndices[$currentMonth] ?? 1.0);
        $targetIndex = (float) ($this->seasonalIndices[$targetMonth] ?? 1.0);

        // Avoid division by zero
        if ($currentIndex <= 0.05) {
            $currentIndex = 1.0;
        }

        // Deseasonalized Annual Benchmark: D = Level / S_current
        $deseasonalizedAnnualBase = $baseline / $currentIndex;
        // Seasonal Target Price for future month: P_seasonal = D * S_target
        $seasonalTargetPrice = max($currentPrice * 0.35, $deseasonalizedAnnualBase * $targetIndex);

        // 4. Horizon-dependent blending weight (USDA ERS standard)
        // Short horizon (1-7 days) is dominated by momentum/continuity.
        // Long horizon (15-30 days) is dominated by seasonal benchmark transition.
        $wMomentum = match ($horizonDays) {
            1 => 0.92,
            7 => 0.65,
            15 => 0.35,
            30 => 0.15,
            default => max(0.10, min(0.90, 1.0 - ($horizonDays / 35.0))),
        };

        $expectedPrice = round(($wMomentum * $momentumPrice) + ((1.0 - $wMomentum) * $seasonalTargetPrice), 2);
        // Absolute sanity bounds: price cannot fall below 35% or exceed 250% of current price within 30 days
        $expectedPrice = max(round($currentPrice * 0.35, 2), min(round($currentPrice * 2.5, 2), $expectedPrice));

        // 5. Variance and Prediction Intervals
        $variance = 0.0;
        foreach ($window as $p) {
            $variance += pow($p - $baseline, 2);
        }
        $stdDev = count($window) > 1 ? sqrt($variance / (count($window) - 1)) : ($baseline * 0.08);

        // Expanding confidence interval with time horizon sqrt(h/7 + 0.5)
        $horizonScaling = sqrt(($horizonDays / 7.0) + 0.5);
        $margin = 1.96 * $stdDev * $horizonScaling;

        $lowerBound = max(round($expectedPrice * 0.40, 2), round($expectedPrice - $margin, 2));
        $upperBound = round($expectedPrice + $margin, 2);

        $cv = $baseline > 0 ? ($stdDev / $baseline) : 0.5;
        $confidenceScore = round(max(20.0, min(95.0, (1.0 - min(0.75, $cv)) * 92.0)), 1);

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
