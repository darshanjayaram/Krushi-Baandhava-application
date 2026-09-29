<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.agmarknet.gov.in/v1';
$headers = [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

$payload = [
    'state_id' => 16, // Karnataka
    'commodity_id' => null,
    'from_date' => '2026-09-01',
    'to_date' => '2026-09-28',
];

echo "Sending POST to {$baseUrl}/daily-price-arrival/report ...\n";
try {
    $res = Http::timeout(15)->withOptions(['verify' => false])->withHeaders($headers)->post("{$baseUrl}/daily-price-arrival/report", $payload);
    echo "Status: " . $res->status() . "\n";
    echo "Headers: " . json_encode($res->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Body: " . substr($res->body(), 0, 1000) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
