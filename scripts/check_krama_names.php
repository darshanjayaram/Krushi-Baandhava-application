<?php
// List data sources and KRAMA mappings
$sources = App\Models\DataSource::all(['id', 'name']);
echo "=== All Data Sources ===" . PHP_EOL;
foreach ($sources as $d) {
    echo "  {$d->id}: {$d->name}" . PHP_EOL;
}

// Find KRAMA
$krama = $sources->first(fn($d) => stripos($d->name, 'KRAMA') !== false || stripos($d->name, 'Karnataka') !== false);

if (!$krama) {
    echo PHP_EOL . "Could not identify KRAMA source." . PHP_EOL;
    return;
}

echo PHP_EOL . "Using source: {$krama->name} (ID {$krama->id})" . PHP_EOL . PHP_EOL;

echo "=== source_market_name (what KRAMA sends) → our DB market name ===" . PHP_EOL;
$mappings = App\Models\MarketSourceMapping::where('data_source_id', $krama->id)
    ->with('market:id,name')
    ->orderBy('source_market_name')
    ->get();

foreach ($mappings as $m) {
    $ourName = $m->market ? $m->market->name : '(no market)';
    echo "  '{$m->source_market_name}' → '{$ourName}'" . PHP_EOL;
}

echo PHP_EOL . "Total mappings: " . $mappings->count() . PHP_EOL;
