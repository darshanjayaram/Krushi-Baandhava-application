<?php
// Verify all 3 fixes

$analyticsService = app(App\Services\Analytics\HistoricalAnalyticsService::class);

// Test Paddy (scope: market) and Ragi (scope: market_calibrated)
$crops = App\Models\Crop::whereIn('slug', ['paddy', 'ragi', 'tomato'])->get(['id', 'name', 'slug']);

foreach ($crops as $crop) {
    $latestPrice = App\Models\MarketPrice::karnataka()
        ->where('crop_id', $crop->id)
        ->latest('price_date')
        ->first(['market_id', 'variety_id', 'modal_price']);

    $result = $analyticsService->getSeasonalAnalysis(
        $crop->id,
        $latestPrice?->market_id,
        $latestPrice?->variety_id
    );

    echo "=== {$crop->name} | scope={$result['scope']} ===" . PHP_EOL;

    // FIX 3: check badge_en exists on best_months
    echo "FIX 3 — best_months badge_en:" . PHP_EOL;
    foreach ($result['best_months'] as $bm) {
        echo "  #{$bm['rank']} {$bm['month_name_en']}: badge_en='" . ($bm['badge_en'] ?? 'MISSING ❌') . "' badge_kn='" . ($bm['badge_kn'] ?? 'MISSING ❌') . "'" . PHP_EOL;
    }

    // FIX 1: check lean months have low bar, peak months have high bar
    echo "FIX 1 — bar heights (lean must be < 26, peak must be > 50):" . PHP_EOL;
    $hasBug = false;
    foreach ($result['monthly_profile'] as $m) {
        $tier = $m['tier'] ?? 'mid';
        $h = $m['bar_height_percent'];
        if ($tier === 'lo' && $h > 26) {
            echo "  ❌ {$m['name_en']} (lean) has bar={$h}% — too high!" . PHP_EOL;
            $hasBug = true;
        } elseif ($tier === 'pk' && $h < 30) {
            echo "  ❌ {$m['name_en']} (peak) has bar={$h}% — too low!" . PHP_EOL;
            $hasBug = true;
        }
    }
    if (!$hasBug) {
        // Show all tiers & heights
        foreach ($result['monthly_profile'] as $m) {
            if (($m['observations'] ?? 0) > 0) {
                $flag = ($m['tier'] === 'lo' && $m['bar_height_percent'] <= 26) ? '✅' : 
                        ($m['tier'] === 'pk' ? '⭐' : '  ');
                echo "  {$flag} {$m['name_en']}: tier={$m['tier']} bar={$m['bar_height_percent']}%" . PHP_EOL;
            }
        }
    }

    // FIX 2: scope disclosure
    echo "FIX 2 — scope disclosure: " . PHP_EOL;
    echo "  scope='" . ($result['scope'] ?? '?') . "' — will show 🔄 pill: " . (($result['scope'] ?? '') === 'market_calibrated' ? 'YES ✅' : 'NO (direct data)') . PHP_EOL;

    echo PHP_EOL;
}
