<?php
$s = app(App\Services\Analytics\HistoricalAnalyticsService::class);
$c = App\Models\Crop::where('slug', 'sunflower')->first();
$lp = App\Models\MarketPrice::karnataka()->where('crop_id', $c->id)->latest('price_date')->first(['market_id', 'variety_id']);
$r = $s->getSeasonalAnalysis($c->id, $lp?->market_id, $lp?->variety_id);

echo "Sunflower | scope=" . $r['scope'] . PHP_EOL;
echo "FIX 2 — 🔄 pill shows: " . ($r['scope'] === 'market_calibrated' ? 'YES ✅' : 'NO') . PHP_EOL;
echo "FIX 3 — badge_en on best_months:" . PHP_EOL;
foreach ($r['best_months'] as $bm) {
    echo "  #{$bm['rank']} {$bm['month_name_en']}: '{$bm['badge_en']}' / '{$bm['badge_kn']}'" . PHP_EOL;
}
echo "FIX 1 — lean month bars:" . PHP_EOL;
foreach (collect($r['monthly_profile'])->where('tier', 'lo') as $m) {
    echo "  {$m['name_en']}: bar={$m['bar_height_percent']}% " . ($m['bar_height_percent'] <= 26 ? '✅' : '❌ too high') . PHP_EOL;
}
