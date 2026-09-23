<?php

namespace App\Services\Forecast;

use App\Models\Crop;
use App\Models\ForecastMetric;
use App\Models\ForecastModel;
use App\Models\ForecastRun;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\PriceDailyStatistic;
use App\Models\PriceForecast;
use App\Models\PriceMonthlyStatistic;
use App\Services\Forecast\Contracts\ForecastModelInterface;
use App\Services\Forecast\Models\HoltsLinearTrendModel;
use App\Services\Forecast\Models\SeasonalDecompositionModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ForecastingEngineService
{
    /**
     * Minimum observation threshold required within preceding 90 days.
     */
    public const MIN_OBSERVATIONS = 30;

    /**
     * Standard horizons in days.
     */
    public const HORIZONS = [1, 7, 15, 30];

    /**
     * Generate projections across all standard horizons for a given crop and market.
     */
    public function getForecastsForCrop(int $cropId, ?int $marketId = null): array
    {
        // 1. Fetch historical prices in last 90 days
        $startDate = Carbon::today()->subDays(90)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $query = MarketPrice::karnataka()
            ->where('crop_id', $cropId)
            ->whereBetween('price_date', [$startDate, $endDate])
            ->orderBy('price_date', 'asc');

        if ($marketId !== null) {
            $query->where('market_id', $marketId);
        }

        $priceRecords = $query->get();
        $observationsCount = $priceRecords->count();

        // Check Data Sufficiency
        if ($observationsCount < self::MIN_OBSERVATIONS) {
            return [
                'is_sufficient' => false,
                'observations_count' => $observationsCount,
                'min_required' => self::MIN_OBSERVATIONS,
                'message_kn' => "ವಿಶ್ವಾಸಾರ್ಹ ಮುನ್ಸೂಚನೆಗೆ ಕನಿಷ್ಠ " . self::MIN_OBSERVATIONS . " ದಿನಗಳ ಮಾರುಕಟ್ಟೆ ದರಗಳು ಅಗತ್ಯವಿದೆ (ಕೇವಲ {$observationsCount} ದಿನಗಳ ದರ ಲಭ್ಯವಿದೆ).",
                'message_en' => "Insufficient historical data for a reliable estimate. Minimum " . self::MIN_OBSERVATIONS . " observations required (found {$observationsCount}).",
                'horizons' => [],
                'current_price' => (float) ($priceRecords->last()?->modal_price ?? 0),
            ];
        }

        $historicalPrices = $priceRecords->pluck('modal_price')->map(fn ($p) => (float) $p)->toArray();
        $currentPrice = end($historicalPrices) ?: 0.0;

        // Model Selection
        $model = $this->resolveModelForCrop($cropId);

        $horizonsOutput = [];
        foreach (self::HORIZONS as $hDays) {
            $targetDate = Carbon::today()->addDays($hDays)->toDateString();
            $proj = $model->forecast($historicalPrices, $hDays);

            $expected = $proj['expected_price'];
            $pctChange = $currentPrice > 0 ? round((($expected - $currentPrice) / $currentPrice) * 100, 1) : 0.0;

            $direction = 'neutral';
            if ($pctChange > 1.0) {
                $direction = 'up';
            } elseif ($pctChange < -1.0) {
                $direction = 'down';
            }

            $horizonsOutput[] = [
                'horizon_days' => $hDays,
                'target_date' => $targetDate,
                'target_date_formatted' => Carbon::parse($targetDate)->format('d M Y'),
                'expected_price' => $expected,
                'lower_bound' => $proj['lower_bound'],
                'upper_bound' => $proj['upper_bound'],
                'confidence_score' => $proj['confidence_score'],
                'percentage_change' => $pctChange,
                'direction' => $direction,
                'rmse' => $proj['rmse'],
                'mae' => $proj['mae'],
                'label_kn' => match ($hDays) {
                    1 => 'ನಾಳೆ (+1 ದಿನ)',
                    7 => 'ಮುಂದಿನ 7 ದಿನಗಳು (+7 ದಿನ)',
                    15 => 'ಮುಂದಿನ 15 ದಿನಗಳು (+15 ದಿನ)',
                    30 => 'ಮುಂದಿನ 30 ದಿನಗಳು (+30 ದಿನ)',
                    default => "+{$hDays} ದಿನಗಳು",
                },
                'label_en' => match ($hDays) {
                    1 => 'Tomorrow (+1 Day)',
                    7 => 'Next 7 Days (+7D)',
                    15 => 'Next 15 Days (+15D)',
                    30 => 'Next 30 Days (+30D)',
                    default => "+{$hDays} Days",
                },
            ];
        }

        return [
            'is_sufficient' => true,
            'observations_count' => $observationsCount,
            'min_required' => self::MIN_OBSERVATIONS,
            'model_name' => $model->getName(),
            'model_code' => $model->getCode(),
            'current_price' => $currentPrice,
            'horizons' => $horizonsOutput,
            'disclaimer_kn' => 'ಇದು ಹಿಂದಿನ ದರಗಳ ಆಧಾರಿತ ಗಣಿತೀಯ ಮುನ್ಸೂಚನೆ ಮಾತ್ರ. ಹವಾಮಾನ, ಮಾರುಕಟ್ಟೆ ಬೇಡಿಕೆ ಮತ್ತು ಸರ್ಕಾರದ ನೀತಿಗಳಿಂದ ನೈಜ ದರಗಳು ವ್ಯತ್ಯಾಸವಾಗಬಹುದು.',
            'disclaimer_en' => 'Statistical projections based on historical patterns. Subject to market fluctuations, weather, and policy shifts.',
        ];
    }

    /**
     * Orchestrate batch forecast generation for all active crops in Karnataka.
     */
    public function runAllForecasts(): ForecastRun
    {
        $activeModelRecord = ForecastModel::firstOrCreate(
            ['code' => 'holts_linear'],
            [
                'name' => "Holt's Linear Exponential Smoothing",
                'version' => '1.0',
                'parameters' => ['alpha' => 0.35, 'beta' => 0.15],
                'is_active' => true,
            ]
        );

        $run = ForecastRun::create([
            'model_id' => $activeModelRecord->id,
            'started_at' => Carbon::now(),
            'status' => 'running',
            'total_predictions' => 0,
        ]);

        $crops = Crop::where('is_active', true)->get();
        $totalPredictions = 0;

        foreach ($crops as $crop) {
            $forecastResult = $this->getForecastsForCrop($crop->id, null);

            if (! $forecastResult['is_sufficient']) {
                continue;
            }

            foreach ($forecastResult['horizons'] as $h) {
                PriceForecast::updateOrCreate(
                    [
                        'crop_id' => $crop->id,
                        'variety_id' => null,
                        'market_id' => null, // State Benchmark
                        'forecast_date' => $h['target_date'],
                        'horizon_days' => $h['horizon_days'],
                    ],
                    [
                        'run_id' => $run->id,
                        'expected_price' => $h['expected_price'],
                        'lower_bound' => $h['lower_bound'],
                        'upper_bound' => $h['upper_bound'],
                        'confidence_score' => $h['confidence_score'],
                        'data_points_used' => $forecastResult['observations_count'],
                    ]
                );
                $totalPredictions++;
            }
        }

        $run->update([
            'completed_at' => Carbon::now(),
            'status' => 'completed',
            'total_predictions' => $totalPredictions,
        ]);

        return $run;
    }

    /**
     * Perform rolling walk-forward backtesting and track error metrics.
     */
    public function backtest(ForecastModelInterface $model, int $cropId, ?int $marketId = null, int $horizonDays = 7): array
    {
        $prices = MarketPrice::karnataka()
            ->where('crop_id', $cropId)
            ->when($marketId !== null, fn ($q) => $q->where('market_id', $marketId))
            ->orderBy('price_date', 'asc')
            ->pluck('modal_price')
            ->map(fn ($p) => (float) $p)
            ->toArray();

        $n = count($prices);
        $minTrain = 20;

        if ($n < ($minTrain + $horizonDays)) {
            return [
                'mae' => 0.0,
                'rmse' => 0.0,
                'mape' => 0.0,
                'directional_accuracy' => 0.0,
                'test_windows' => 0,
            ];
        }

        $errors = [];
        $absolutePercentageErrors = [];
        $directionalHits = 0;
        $windowsCount = 0;

        // Rolling walk-forward loop
        for ($t = $minTrain; $t <= ($n - $horizonDays); $t++) {
            $trainWindow = array_slice($prices, 0, $t);
            $actual = $prices[$t + $horizonDays - 1];
            $lastKnown = $trainWindow[count($trainWindow) - 1];

            $proj = $model->forecast($trainWindow, $horizonDays);
            $pred = $proj['expected_price'];

            $err = $actual - $pred;
            $errors[] = $err;
            if ($actual > 0) {
                $absolutePercentageErrors[] = (abs($err) / $actual) * 100.0;
            }

            // Directional agreement
            $actualDir = $actual >= $lastKnown;
            $predDir = $pred >= $lastKnown;
            if ($actualDir === $predDir) {
                $directionalHits++;
            }

            $windowsCount++;
        }

        $mae = $windowsCount > 0 ? (array_sum(array_map('abs', $errors)) / $windowsCount) : 0.0;
        $sumSq = 0.0;
        foreach ($errors as $e) {
            $sumSq += pow($e, 2);
        }
        $rmse = $windowsCount > 0 ? sqrt($sumSq / $windowsCount) : 0.0;
        $mape = count($absolutePercentageErrors) > 0 ? (array_sum($absolutePercentageErrors) / count($absolutePercentageErrors)) : 0.0;
        $directionalAccuracy = $windowsCount > 0 ? (($directionalHits / $windowsCount) * 100.0) : 0.0;

        // Record in forecast_metrics if model exists in DB
        $dbModel = ForecastModel::where('code', $model->getCode())->first();
        if ($dbModel) {
            ForecastMetric::updateOrCreate(
                [
                    'model_id' => $dbModel->id,
                    'crop_id' => $cropId,
                    'variety_id' => null,
                    'market_id' => $marketId,
                    'horizon_days' => $horizonDays,
                ],
                [
                    'mae' => round($mae, 2),
                    'rmse' => round($rmse, 2),
                    'mape' => round($mape, 2),
                    'directional_accuracy' => round($directionalAccuracy, 2),
                ]
            );
        }

        return [
            'mae' => round($mae, 2),
            'rmse' => round($rmse, 2),
            'mape' => round($mape, 2),
            'directional_accuracy' => round($directionalAccuracy, 2),
            'test_windows' => $windowsCount,
        ];
    }

    /**
     * Resolve active forecast model for commodity.
     */
    protected function resolveModelForCrop(int $cropId): ForecastModelInterface
    {
        // Check if multi-year seasonal indices exist
        $seasonalIndices = PriceMonthlyStatistic::where('crop_id', $cropId)
            ->whereNull('market_id')
            ->pluck('seasonal_index', 'month')
            ->map(fn ($idx) => (float) $idx)
            ->toArray();

        // If strong seasonal data exists with variance across months, use seasonal decomposition
        if (count($seasonalIndices) >= 6) {
            return new SeasonalDecompositionModel($seasonalIndices);
        }

        // Default to Holt's Linear
        return new HoltsLinearTrendModel(alpha: 0.35, beta: 0.15);
    }
}
