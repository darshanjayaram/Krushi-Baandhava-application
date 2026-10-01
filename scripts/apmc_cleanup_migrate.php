<?php
/**
 * APMC Cleanup - LIVE Migration Script (wrapped in DB transaction)
 * Run: php artisan tinker --execute="require 'scripts/apmc_cleanup_migrate.php';"
 *
 * Safety:
 *  - Runs inside a DB transaction: if anything fails, ALL changes are rolled back automatically.
 *  - Skips records where name doesn't actually change (avoids unnecessary writes).
 *  - Prints every change so you can inspect the output.
 */

use Illuminate\Support\Facades\DB;

$updated = 0;
$skipped = 0;

DB::beginTransaction();

try {
    $markets = App\Models\Market::where('name', 'like', '% APMC%')
        ->orWhere('name', 'like', 'APMC %')
        ->orWhere('name_kn', 'like', '%ಎಪಿಎಂಸಿ%')
        ->get(['id', 'name', 'name_kn', 'code']);

    echo "=== APMC LIVE CLEANUP (in transaction) ===" . PHP_EOL;
    echo "Markets found: " . $markets->count() . PHP_EOL . PHP_EOL;

    foreach ($markets as $m) {
        $newName = trim(preg_replace('/\s+APMC\s*$/i', '', $m->name));
        $newKn   = $m->name_kn ? trim(preg_replace('/\s*ಎಪಿಎಂಸಿ\s*$/u', '', $m->name_kn)) : $m->name_kn;

        $nameChanged = $newName !== $m->name;
        $knChanged   = $newKn !== $m->name_kn;

        if (!$nameChanged && !$knChanged) {
            $skipped++;
            continue;
        }

        $changes = [];
        if ($nameChanged) $changes['name'] = $newName;
        if ($knChanged)   $changes['name_kn'] = $newKn;

        App\Models\Market::where('id', $m->id)->update($changes);

        echo "✅ ID {$m->id} [{$m->code}]" . PHP_EOL;
        if ($nameChanged) echo "   name : '{$m->name}' → '{$newName}'" . PHP_EOL;
        if ($knChanged)   echo "   kn   : '{$m->name_kn}' → '{$newKn}'" . PHP_EOL;
        $updated++;
    }

    DB::commit();

    echo PHP_EOL . "=== DONE ===" . PHP_EOL;
    echo "Updated : {$updated}" . PHP_EOL;
    echo "Skipped : {$skipped} (no APMC suffix found)" . PHP_EOL;
    echo "Transaction committed ✅" . PHP_EOL;

} catch (\Throwable $e) {
    DB::rollBack();
    echo PHP_EOL . "❌ ERROR - transaction rolled back. No changes made." . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}
