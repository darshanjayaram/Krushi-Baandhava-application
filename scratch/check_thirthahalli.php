<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;

echo "=== MARKETS MATCHING THIRTHAHALLI ===" . PHP_EOL;
$markets = Market::where('name', 'like', '%Thirthahalli%')->get();
foreach ($markets as $m) {
    echo "Market ID: {$m->id} | Name: {$m->name} | Code: {$m->code}" . PHP_EOL;
}

echo PHP_EOL . "=== RAW RECORDS MATCHING THIRTHAHALLI ===" . PHP_EOL;
$raws = MarketPriceRaw::where('payload', 'like', '%Thirthahalli%')->orWhere('payload', 'like', '%Tirthahalli%')->get();
echo "Found " . $raws->count() . " raw records" . PHP_EOL;
foreach ($raws as $r) {
    $payload = $r->payload;
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo PHP_EOL . "=== CANONICAL PRICES FOR THIRTHAHALLI ===" . PHP_EOL;
$prices = MarketPrice::whereHas('market', fn ($m) => $m->where('name', 'like', '%Thirthahalli%'))
    ->with(['variety', 'crop', 'market'])
    ->get();

foreach ($prices as $p) {
    echo "Crop: {$p->crop->name} | Variety: " . ($p->variety?->name ?? 'NULL') . " | Raw Record ID: {$p->raw_record_id} | Modal: ₹{$p->modal_price} | Date: {$p->price_date}" . PHP_EOL;
}
