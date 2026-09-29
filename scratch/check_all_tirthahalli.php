<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MarketPriceRaw;

$allTirthahalli = MarketPriceRaw::where('payload', 'like', '%"market":"TIRTHAHALLI"%')
    ->orWhere('payload', 'like', '%"market": "TIRTHAHALLI"%')
    ->get();

echo "Total raw records for TIRTHAHALLI: " . $allTirthahalli->count() . PHP_EOL;

foreach ($allTirthahalli as $r) {
    $p = $r->payload;
    echo "- Crop: " . ($p['crop'] ?? '') . " | Variety: " . ($p['variety'] ?? '') . " | Grade: " . ($p['grade'] ?? '') . " | Modal: " . ($p['modal'] ?? '') . " | Rejected: " . ($r->is_rejected ? 'YES (' . $r->rejection_reason . ')' : 'NO') . PHP_EOL;
}
