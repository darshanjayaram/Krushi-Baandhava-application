<?php
/**
 * APMC Cleanup - Dry Run Script
 * Run: php artisan tinker --execute="require 'scripts/apmc_cleanup_dryrun.php';"
 */

$markets = App\Models\Market::where('name', 'like', '% APMC%')
    ->orWhere('name', 'like', 'APMC %')
    ->orWhere('name_kn', 'like', '%ಎಪಿಎಂಸಿ%')
    ->get(['id', 'name', 'name_kn', 'code', 'market_type']);

echo "=== DRY RUN: Markets needing APMC cleanup ===" . PHP_EOL;
echo "Total: " . $markets->count() . PHP_EOL . PHP_EOL;

$markets->each(function ($m) {
    $newName = trim(preg_replace('/\s+APMC\s*$/i', '', $m->name));
    $newKn   = $m->name_kn ? trim(preg_replace('/\s*ಎಪಿಎಂಸಿ\s*$/u', '', $m->name_kn)) : null;

    $nameChanged = $newName !== $m->name;
    $knChanged   = $newKn !== $m->name_kn;

    if ($nameChanged || $knChanged) {
        echo "ID {$m->id} [{$m->code}]" . PHP_EOL;
        if ($nameChanged) echo "  name : '{$m->name}' → '{$newName}'" . PHP_EOL;
        if ($knChanged)   echo "  kn   : '{$m->name_kn}' → '{$newKn}'" . PHP_EOL;
    }
});

echo PHP_EOL . "=== No changes made (dry run) ===" . PHP_EOL;
