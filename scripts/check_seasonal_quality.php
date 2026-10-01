<?php
// Deep check on Best Months to Sell seasonal analysis quality

$analyticsService = app(App\Services\Analytics\HistoricalAnalyticsService::class);

$testCrops = App\Models\Crop::where('is_active', true)
    ->whereIn('slug', ['tomato', 'onion', 'ragi', 'maize', 'paddy', 'bengal-gram', 'sunflower', 'cotton'])
    ->get(['id', 'name', 'slug']);

echo "=== BEST MONTHS TO SELL — Quality Check ===" . PHP_EOL . PHP_EOL;

foreach ($testCrops as $crop) {
    $latestPrice = App\Models\MarketPrice::karnataka()
        ->where('crop_id', $crop->id)
        ->latest('price_date')
        ->first(['market_id', 'variety_id', 'modal_price', 'price_date']);

    $result = $analyticsService->getSeasonalAnalysis(
        $crop->id,
        $latestPrice?->market_id,
        $latestPrice?->variety_id
    );

    echo "Crop: {$crop->name}" . PHP_EOL;
    echo "  Sufficient    : " . ($result['is_sufficient'] ? 'YES ✅' : 'NO ❌') . PHP_EOL;
    echo "  Distinct months: " . ($result['distinct_months'] ?? 0) . PHP_EOL;
    echo "  Scope         : " . ($result['scope'] ?? '-') . PHP_EOL;
    echo "  Annual baseline: ₹" . ($result['annual_baseline'] ?? 0) . PHP_EOL;
    
    if (!empty($result['best_months'])) {
        echo "  Best months   :" . PHP_EOL;
        foreach ($result['best_months'] as $bm) {
            $sign = $bm['premium_percent'] >= 0 ? '+' : '';
            echo "    #{$bm['rank']} {$bm['month_name_en']}: ₹{$bm['avg_price']} (index={$bm['seasonal_index']} | {$sign}{$bm['premium_percent']}% vs baseline)" . PHP_EOL;
        }
    }

    // Check all 12 months have data
    $profile = $result['monthly_profile'] ?? [];
    $missingMonths = collect($profile)->where('observations', 0)->pluck('name_en')->join(', ');
    $monthsWithData = collect($profile)->where('observations', '>', 0)->count();
    echo "  Months w/ data : {$monthsWithData}/12" . ($missingMonths ? " (missing: {$missingMonths})" : '') . PHP_EOL;

    // Check if seasonal index math is sound (should average to ~1.0)
    $validIndices = collect($profile)->where('seasonal_index', '>', 0)->pluck('seasonal_index');
    if ($validIndices->count() > 0) {
        $avgIdx = round($validIndices->avg(), 4);
        $minIdx = round($validIndices->min(), 4);
        $maxIdx = round($validIndices->max(), 4);
        $spread = round(($maxIdx - $minIdx) * 100, 1);
        $soundMath = abs($avgIdx - 1.0) < 0.05 ? '✅ Sound' : '⚠️ Skewed (avg != 1.0)';
        echo "  Index avg/min/max: {$avgIdx} / {$minIdx} / {$maxIdx}  spread={$spread}%  {$soundMath}" . PHP_EOL;
    }

    echo "  Summary EN    : " . ($result['lead_summary_en'] ?? '-') . PHP_EOL;
    echo PHP_EOL;
}

// Cross-check: state-level for Paddy (most data)
echo "=== STATE-LEVEL PADDY (no market filter) ===" . PHP_EOL;
$paddy = App\Models\Crop::where('slug', 'paddy')->first();
if ($paddy) {
    $stateResult = $analyticsService->getSeasonalAnalysis($paddy->id, null, null);
    echo "Distinct months : " . $stateResult['distinct_months'] . PHP_EOL;
    echo "Annual baseline : ₹" . $stateResult['annual_baseline'] . PHP_EOL;
    echo "Best months:" . PHP_EOL;
    foreach ($stateResult['best_months'] as $bm) {
        echo "  #{$bm['rank']} {$bm['month_name_en']}: ₹{$bm['avg_price']} ({$bm['premium_percent']}% above baseline)" . PHP_EOL;
    }
    echo PHP_EOL;
    echo "12-month profile (seasonal index):" . PHP_EOL;
    foreach ($stateResult['monthly_profile'] as $m) {
        $bar = str_repeat('█', max(0, (int)($m['bar_height_percent'] / 10)));
        $tier = str_pad($m['tier'] ?? '?', 12);
        $obs = str_pad($m['observations'], 5);
        echo "  " . str_pad($m['name_en'], 10) . " | idx=" . str_pad($m['seasonal_index'], 6) . " | ₹" . str_pad($m['avg_price'], 8) . " | {$tier} | obs={$obs} | {$bar}" . PHP_EOL;
    }
}
