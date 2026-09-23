<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\ForecastModel;
use App\Services\Forecast\ForecastingEngineService;
use App\Services\Forecast\Models\HoltsLinearTrendModel;
use Illuminate\Console\Command;

class GeneratePriceForecastsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'krushi:generate-forecasts 
                            {--crop= : Specific crop slug or ID}
                            {--market= : Specific market ID}
                            {--backtest : Run walk-forward backtesting}';

    /**
     * The console command description.
     */
    protected $description = 'Generate statistical price projections and confidence bounds for Karnataka crops';

    /**
     * Execute the console command.
     */
    public function handle(ForecastingEngineService $forecastingService): int
    {
        $this->info('Starting Krushi Baandhava Price Forecasting Engine...');

        $cropParam = $this->option('crop');
        $marketParam = $this->option('market') ? (int) $this->option('market') : null;
        $doBacktest = $this->option('backtest');

        if ($cropParam) {
            $crop = is_numeric($cropParam) ? Crop::find($cropParam) : Crop::where('slug', $cropParam)->first();
            if (! $crop) {
                $this->error("Crop '{$cropParam}' not found.");
                return Command::FAILURE;
            }

            $this->info("Generating forecasts for crop: {$crop->name} ({$crop->name_kn})...");
            $forecast = $forecastingService->getForecastsForCrop($crop->id, $marketParam);

            if (! $forecast['is_sufficient']) {
                $this->warn("Data Insufficiency: {$forecast['message_en']}");
                return Command::SUCCESS;
            }

            $this->table(
                ['Horizon', 'Date', 'Expected (₹)', 'Lower Bound', 'Upper Bound', 'Confidence', 'Change'],
                array_map(fn ($h) => [
                    $h['label_en'],
                    $h['target_date_formatted'],
                    '₹' . number_format($h['expected_price'], 2),
                    '₹' . number_format($h['lower_bound'], 2),
                    '₹' . number_format($h['upper_bound'], 2),
                    $h['confidence_score'] . '%',
                    ($h['percentage_change'] >= 0 ? '+' : '') . $h['percentage_change'] . '%',
                ], $forecast['horizons'])
            );

            if ($doBacktest) {
                $this->info('Running walk-forward backtesting (7-day horizon)...');
                $model = new HoltsLinearTrendModel();
                $metrics = $forecastingService->backtest($model, $crop->id, $marketParam, 7);
                $this->line("Backtest results: MAE=₹{$metrics['mae']}, RMSE=₹{$metrics['rmse']}, MAPE={$metrics['mape']}%, Directional Accuracy={$metrics['directional_accuracy']}% ({$metrics['test_windows']} windows)");
            }
        } else {
            $this->info('Generating batch forecasts for all active Karnataka commodities...');
            $run = $forecastingService->runAllForecasts();
            $this->info("Completed forecast batch run #{$run->id}: {$run->total_predictions} predictions recorded.");
        }

        $this->info('Forecasting process finished successfully.');

        return Command::SUCCESS;
    }
}
