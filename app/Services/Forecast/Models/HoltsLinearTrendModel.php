<?php

namespace App\Services\Forecast\Models;

use App\Services\Forecast\Contracts\ForecastModelInterface;

class HoltsLinearTrendModel implements ForecastModelInterface
{
    protected float $alpha;
    protected float $beta;

    public function __construct(float $alpha = 0.35, float $beta = 0.15)
    {
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

    public function getName(): string
    {
        return "Holt's Linear Exponential Smoothing";
    }

    public function getCode(): string
    {
        return 'holts_linear';
    }

    /**
     * Compute projection using Holt's Linear Exponential Smoothing.
     *
     * L_t = alpha * Y_t + (1 - alpha) * (L_{t-1} + T_{t-1})
     * T_t = beta * (L_t - L_{t-1}) + (1 - beta) * T_{t-1}
     * Y_hat_{t+h} = L_t + h * T_t
     */
    public function forecast(array $historicalPrices, int $horizonDays): array
    {
        $n = count($historicalPrices);
        if ($n < 2) {
            $val = $n === 1 ? (float) $historicalPrices[0] : 0.0;
            return [
                'expected_price' => $val,
                'lower_bound' => $val,
                'upper_bound' => $val,
                'confidence_score' => 20.0,
                'rmse' => 0.0,
                'mae' => 0.0,
            ];
        }

        // Initialize Level and Trend from first 2 observations
        $level = (float) $historicalPrices[0];
        $trend = (float) ($historicalPrices[1] - $historicalPrices[0]);

        $residuals = [];
        $absoluteErrors = [];

        // Forward smoothing pass
        for ($i = 1; $i < $n; $i++) {
            $actual = (float) $historicalPrices[$i];
            $predictedOneStep = $level + $trend;

            $error = $actual - $predictedOneStep;
            $residuals[] = $error;
            $absoluteErrors[] = abs($error);

            // Update Level & Trend
            $prevLevel = $level;
            $level = ($this->alpha * $actual) + ((1.0 - $this->alpha) * ($level + $trend));
            $trend = ($this->beta * ($level - $prevLevel)) + ((1.0 - $this->beta) * $trend);
        }

        // Prevent runaway negative trend projections
        if ($trend < 0 && ($level + ($horizonDays * $trend)) < ($level * 0.5)) {
            $trend = -($level * 0.05) / max(1, $horizonDays);
        }

        // Calculate h-step forecast
        $expectedPrice = round($level + ($horizonDays * $trend), 2);
        if ($expectedPrice < 0) {
            $expectedPrice = round($level * 0.5, 2);
        }

        // Residual variance and RMSE
        $errorCount = count($residuals);
        $sumSquaredErrors = 0.0;
        foreach ($residuals as $res) {
            $sumSquaredErrors += pow($res, 2);
        }
        $rmse = $errorCount > 0 ? sqrt($sumSquaredErrors / $errorCount) : ($expectedPrice * 0.05);
        $mae = $errorCount > 0 ? (array_sum($absoluteErrors) / $errorCount) : ($expectedPrice * 0.05);

        // Standard error scaled by horizon expansion (sqrt(h))
        $horizonScaling = sqrt(($horizonDays / 7.0) + 0.5);
        $zScore = 1.96; // 95% confidence interval
        $margin = $zScore * $rmse * $horizonScaling;

        $lowerBound = max(0.0, round($expectedPrice - $margin, 2));
        $upperBound = round($expectedPrice + $margin, 2);

        // Confidence score percentage (higher sample & lower RMSE -> higher confidence)
        $avgPrice = array_sum($historicalPrices) / $n;
        $cv = $avgPrice > 0 ? ($rmse / $avgPrice) : 0.5; // Coefficient of variation
        $rawConfidence = (1.0 - min(0.8, $cv)) * 100.0;
        // Dampen confidence for longer horizons
        $horizonDiscount = match ($horizonDays) {
            1 => 1.0,
            7 => 0.95,
            15 => 0.88,
            30 => 0.80,
            default => 0.75,
        };
        $confidenceScore = round(max(20.0, min(96.0, $rawConfidence * $horizonDiscount)), 1);

        return [
            'expected_price' => $expectedPrice,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
            'confidence_score' => $confidenceScore,
            'rmse' => round($rmse, 2),
            'mae' => round($mae, 2),
        ];
    }
}
