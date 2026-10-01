<?php
/**
 * Fix spacing: "Mangaluru(Baikampady)" → "Mangaluru (Baikampady)"
 * The regex ate the space before the opening parenthesis.
 */

use Illuminate\Support\Facades\DB;

$updated = 0;

DB::beginTransaction();

try {
    // Find markets where ( is directly preceded by a word char (no space)
    $markets = App\Models\Market::where('name', 'like', '%(%')
        ->orWhere('name_kn', 'like', '%(%')
        ->get(['id', 'name', 'name_kn', 'code']);

    echo "=== FIX SPACING BEFORE PARENTHESES ===" . PHP_EOL;

    foreach ($markets as $m) {
        // Add space before ( if missing: "City(Sub)" → "City (Sub)"
        $newName = preg_replace('/([^\s])\(/', '$1 (', $m->name);
        $newKn   = $m->name_kn ? preg_replace('/([^\s])\(/', '$1 (', $m->name_kn) : $m->name_kn;

        $nameChanged = $newName !== $m->name;
        $knChanged   = $newKn !== $m->name_kn;

        if (!$nameChanged && !$knChanged) continue;

        $changes = [];
        if ($nameChanged) $changes['name'] = $newName;
        if ($knChanged)   $changes['name_kn'] = $newKn;

        App\Models\Market::where('id', $m->id)->update($changes);

        echo "✅ ID {$m->id}: '{$m->name}' → '{$newName}'" . PHP_EOL;
        $updated++;
    }

    DB::commit();
    echo PHP_EOL . "Updated: {$updated} ✅" . PHP_EOL;

} catch (\Throwable $e) {
    DB::rollBack();
    echo "❌ ERROR - rolled back: " . $e->getMessage() . PHP_EOL;
}
