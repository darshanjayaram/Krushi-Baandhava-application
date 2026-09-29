<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DataSource;
use App\Services\DataSources\DataSourceRegistry;

echo "--- 1. Testing Agmarknet Official ---\n";
$agmarknetDs = DataSource::where('code', 'agmarknet_official')->first();
$agmarknetProvider = DataSourceRegistry::make($agmarknetDs);
echo "Health check:\n";
print_r($agmarknetProvider->healthCheck());

echo "\nFetch sample with from_date & to_date:\n";
$records = $agmarknetProvider->fetch([
    'from_date' => '2026-09-01',
    'to_date' => '2026-09-28',
]);
$count = is_array($records) ? count($records) : iterator_count($records);
echo "Records returned from Agmarknet fetch: {$count}\n";

echo "\n--- 2. Testing KRAMA Karnataka ---\n";
$kramaDs = DataSource::where('code', 'krama_karnataka')->first();
$kramaProvider = DataSourceRegistry::make($kramaDs);
echo "Health check:\n";
print_r($kramaProvider->healthCheck());

echo "\nFetch sample for KRAMA (date = 2026-09-22):\n";
$kramaRecords = $kramaProvider->fetch([
    'date' => '2026-09-22',
]);
$kramaCount = is_array($kramaRecords) ? count($kramaRecords) : iterator_count($kramaRecords);
echo "Records returned from KRAMA fetch: {$kramaCount}\n";
