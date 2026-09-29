<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Crop;
use App\Models\MarketPrice;
use App\Models\Market;

$arecanut = Crop::where('name', 'Arecanut')->first();
echo "=== ARECANUT VARIETIES IN DATABASE ===" . PHP_EOL;
foreach ($arecanut->varieties as $v) {
    echo "ID: {$v->id} | Name: {$v->name} | Name KN: {$v->name_kn}" . PHP_EOL;
}

$thirthahalli = Market::where('name', 'like', '%Thirthahalli%')->first();
echo PHP_EOL . "=== ALL HISTORICAL MARKET PRICES FOR THIRTHAHALLI ARECANUT ===" . PHP_EOL;
$prices = MarketPrice::where('crop_id', $arecanut->id)
    ->where('market_id', $thirthahalli->id)
    ->with('variety')
    ->orderBy('price_date', 'desc')
    ->get();

foreach ($prices as $p) {
    echo "Date: {$p->price_date} | Variety: " . ($p->variety?->name ?? 'NULL') . " | Modal: ₹{$p->modal_price}" . PHP_EOL;
}
