<?php
// Check "What's next" forecast data quality for top crops

$forecastingService = app(App\Services\Forecast\ForecastingEngineService::class);

// Test on top 5 crops that likely have most data
$crops = App\Models\Crop::where('is_active', true)
    ->whereIn('slug', ['tomato', 'onion', 'ragi', 'maize', 'paddy', 'bengal-gram', 'sunflower'])
    ->orWhereIn('name', ['Tomato', 'Onion', 'Ragi', 'Maize', 'Paddy'])
    ->take(5)
    ->get(['id', 'name', 'slug']);

echo "=== WHAT'S NEXT — Forecast Quality Check ===" . PHP_EOL . PHP_EOL;

foreach ($crops as $crop) {
    // Get a sample market
    $latestPrice = App\Models\MarketPrice::karnataka()
        ->where('crop_id', $crop->id)
        ->where('modal_price', '>', 0)
        ->latest('price_date')
        ->first(['market_id', 'variety_id', 'modal_price', 'price_date']);

    $result = $forecastingService->getForecastsForCrop(
        $crop->id,
        $latestPrice?->market_id,
        $latestPrice?->variety_id,
        (float) ($latestPrice?->modal_price ?? 0)
    );

    echo "Crop: {$crop->name} (ID {$crop->id})" . PHP_EOL;
    echo "  Latest price date : " . ($latestPrice?->price_date ?? 'NO PRICES') . PHP_EOL;
    echo "  Current price     : ₹" . ($latestPrice?->modal_price ?? 0) . PHP_EOL;
    echo "  Sufficient data   : " . ($result['is_sufficient'] ? 'YES ✅' : 'NO ❌') . PHP_EOL;
    echo "  Observations      : " . ($result['observations_count'] ?? 0) . " (min: " . ($result['min_required'] ?? 30) . ")" . PHP_EOL;
    echo "  Scope             : " . ($result['scope'] ?? '-') . PHP_EOL;
    echo "  Model             : " . ($result['model_name'] ?? '-') . PHP_EOL;

    if (!empty($result['horizons'])) {
        echo "  Horizons:" . PHP_EOL;
        foreach ($result['horizons'] as $h) {
            $arrow = $h['direction'] === 'up' ? '↑' : ($h['direction'] === 'down' ? '↓' : '→');
            echo "    +{$h['horizon_days']}d: ₹{$h['expected_price']} [{$h['lower_bound']}–{$h['upper_bound']}] {$arrow} {$h['percentage_change']}%  confidence={$h['confidence_score']}%" . PHP_EOL;
        }
    } else {
        echo "  Message: " . ($result['message_en'] ?? 'No horizons generated') . PHP_EOL;
    }

    echo PHP_EOL;
}

// Check: how many crops have sufficient forecast data at all?
$totalCrops = App\Models\Crop::where('is_active', true)->count();
$sufficientCount = 0;
$insufficientCrops = [];

App\Models\Crop::where('is_active', true)->get(['id', 'name'])->each(function ($crop) use ($forecastingService, &$sufficientCount, &$insufficientCrops) {
    $result = $forecastingService->getForecastsForCrop($crop->id, null, null, null);
    if ($result['is_sufficient']) {
        $sufficientCount++;
    } else {
        $insufficientCrops[] = "{$crop->name} ({$result['observations_count']} obs)";
    }
});

echo "=== SUMMARY ===" . PHP_EOL;
echo "Total active crops     : {$totalCrops}" . PHP_EOL;
echo "With sufficient data   : {$sufficientCount}" . PHP_EOL;
echo "Without forecast       : " . count($insufficientCrops) . PHP_EOL;
if (!empty($insufficientCrops)) {
    echo "  → " . implode(', ', array_slice($insufficientCrops, 0, 15)) . PHP_EOL;
}
