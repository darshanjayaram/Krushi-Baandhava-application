<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MarketPriceRaw;

$r1 = MarketPriceRaw::find(25346);
echo "Raw 25346:" . PHP_EOL;
echo json_encode($r1->payload, JSON_PRETTY_PRINT) . PHP_EOL;

$r2 = MarketPriceRaw::find(25355);
echo "Raw 25355:" . PHP_EOL;
echo json_encode($r2->payload, JSON_PRETTY_PRINT) . PHP_EOL;
