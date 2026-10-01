<?php
// Final verification
$withApmc = App\Models\Market::where('name', 'like', '%APMC%')->count();
$withKn   = App\Models\Market::where('name_kn', 'like', '%ಎಪಿಎಂಸಿ%')->count();

echo "=== FINAL VERIFICATION ===" . PHP_EOL;
echo "Markets with 'APMC' in name   : {$withApmc}" . PHP_EOL;
echo "Markets with 'ಎಪಿಎಂಸಿ' in name_kn: {$withKn}" . PHP_EOL;
echo PHP_EOL . "Sample clean market names:" . PHP_EOL;

App\Models\Market::take(8)->get(['name', 'name_kn'])->each(function ($m) {
    echo "  EN: {$m->name}  |  KN: {$m->name_kn}" . PHP_EOL;
});
