<?php
/**
 * Fix remaining markets where APMC appears before a parenthetical sub-market name.
 * e.g. "Mangaluru APMC (Baikampady)" → "Mangaluru (Baikampady)"
 * e.g. "ಮಂಗಳೂರು ಎಪಿಎಂಸಿ (ಬೈಕಂಪಾಡಿ)" → "ಮಂಗಳೂರು (ಬೈಕಂಪಾಡಿ)"
 */

use Illuminate\Support\Facades\DB;

$updated = 0;

DB::beginTransaction();

try {
    $markets = App\Models\Market::where('name', 'like', '%APMC%')
        ->orWhere('name_kn', 'like', '%ಎಪಿಎಂಸಿ%')
        ->get(['id', 'name', 'name_kn', 'code']);

    echo "=== FIX REMAINING APMC (in transaction) ===" . PHP_EOL;
    echo "Found: " . $markets->count() . PHP_EOL . PHP_EOL;

    foreach ($markets as $m) {
        // Strip ' APMC' anywhere in the name (before parenthetical sub-names too)
        $newName = trim(preg_replace('/\s+APMC\s*/i', '', $m->name));
        // Strip ' ಎಪಿಎಂಸಿ' anywhere in name_kn
        $newKn   = $m->name_kn ? trim(preg_replace('/\s*ಎಪಿಎಂಸಿ\s*/u', '', $m->name_kn)) : $m->name_kn;

        $nameChanged = $newName !== $m->name;
        $knChanged   = $newKn !== $m->name_kn;

        if (!$nameChanged && !$knChanged) continue;

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
    echo "Transaction committed ✅" . PHP_EOL;

} catch (\Throwable $e) {
    DB::rollBack();
    echo PHP_EOL . "❌ ERROR - transaction rolled back." . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}
