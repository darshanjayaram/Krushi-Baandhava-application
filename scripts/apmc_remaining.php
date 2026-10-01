<?php
// Check remaining APMC markets
echo "Remaining markets with APMC in name or name_kn:" . PHP_EOL;
App\Models\Market::where('name', 'like', '%APMC%')
    ->orWhere('name_kn', 'like', '%ಎಪಿಎಂಸಿ%')
    ->get(['id', 'name', 'name_kn', 'code'])
    ->each(function ($m) {
        echo "ID {$m->id} [{$m->code}]  EN: {$m->name}  |  KN: {$m->name_kn}" . PHP_EOL;
    });
