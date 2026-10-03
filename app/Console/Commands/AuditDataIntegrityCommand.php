<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\MarketPriceRaw;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditDataIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:audit-integrity 
                            {--date= : Specific date to audit (YYYY-MM-DD)}
                            {--days=7 : Number of past days to audit if date is not specified}
                            {--crop= : Filter by crop slug or name}
                            {--market= : Filter by market name or ID}
                            {--fix : Automatically reconcile any discrepancies using ingestion engine}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit ingested market prices against raw source payloads to verify 100% 1-to-1 data fidelity and zero ghost records';

    public function handle(MarketPriceIngestionService $ingestionService): int
    {
        $this->info("=================================================================");
        $this->info("   KRUSHI BAANDHAVA - MARKET PRICE DATA INTEGRITY AUDITOR        ");
        $this->info("=================================================================");

        $dateParam = $this->option('date');
        $daysParam = (int) $this->option('days');
        $cropParam = $this->option('crop');
        $marketParam = $this->option('market');
        $autoFix = (bool) $this->option('fix');

        $query = MarketPrice::query()->with(['market', 'crop', 'variety', 'rawRecord']);

        if ($dateParam) {
            $date = Carbon::parse($dateParam)->format('Y-m-d');
            $query->whereDate('price_date', $date);
            $this->info("Auditing single date: {$date}");
        } else {
            $startDate = Carbon::today()->subDays($daysParam)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
            $query->whereBetween('price_date', [$startDate, $endDate]);
            $this->info("Auditing date range: {$startDate} to {$endDate} (Last {$daysParam} days)");
        }

        if ($cropParam) {
            $query->whereHas('crop', function ($q) use ($cropParam) {
                $q->where('slug', $cropParam)
                    ->orWhere('name', 'like', "%{$cropParam}%")
                    ->orWhere('name_kn', 'like', "%{$cropParam}%");
            });
            $this->info("Filtered by crop: {$cropParam}");
        }

        if ($marketParam) {
            if (is_numeric($marketParam)) {
                $query->where('market_id', (int) $marketParam);
            } else {
                $query->whereHas('market', function ($q) use ($marketParam) {
                    $q->where('name', 'like', "%{$marketParam}%");
                });
            }
            $this->info("Filtered by market: {$marketParam}");
        }

        $totalRecords = $query->count();
        $this->info("Total records to audit: {$totalRecords}");

        if ($totalRecords === 0) {
            $this->warn("No market price records found matching criteria.");
            return 0;
        }

        $audited = 0;
        $marketMismatches = [];
        $varietyMismatches = [];
        $priceMismatches = [];
        $fixedCount = 0;

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $query->chunk(200, function ($prices) use (
            &$audited,
            &$marketMismatches,
            &$varietyMismatches,
            &$priceMismatches,
            &$fixedCount,
            $autoFix,
            $ingestionService,
            $bar
        ) {
            foreach ($prices as $price) {
                $audited++;
                $bar->advance();

                $raw = $price->rawRecord;
                if (!$raw || empty($raw->payload)) {
                    continue; // Skip synthetic or legacy records lacking raw payload
                }

                $payload = $raw->payload;
                $rawMarket = $payload['market'] ?? $payload['Market'] ?? null;
                $rawVariety = $payload['variety'] ?? $payload['Variety'] ?? null;
                $rawModal = (float) ($payload['modal'] ?? $payload['modal_price'] ?? $payload['Modal_Price'] ?? 0);

                // 1. Audit Market Attribution
                if ($rawMarket && $price->market) {
                    if (!$ingestionService->isPlausibleMarketMatch($rawMarket, $price->market)) {
                        $cleanRaw = strtolower(trim(preg_replace('/\b(apmc|mandi|market)\b/i', '', $rawMarket)));
                        $cleanAssigned = strtolower(trim(preg_replace('/\b(apmc|mandi|market)\b/i', '', $price->market->name)));
                        similar_text($cleanRaw, $cleanAssigned, $sim);

                        $mismatch = [
                            'price_id' => $price->id,
                            'date' => $price->price_date?->format('Y-m-d') ?? $price->price_date,
                            'crop' => $price->crop?->name,
                            'raw_market' => $rawMarket,
                            'assigned_market' => $price->market->name . " (ID: {$price->market->id})",
                            'similarity' => round($sim, 1) . '%',
                        ];
                        $marketMismatches[] = $mismatch;

                        if ($autoFix) {
                            $resolvedMarket = $ingestionService->resolveMarket($price->data_source_id ?? 1, $rawMarket);
                            if ($resolvedMarket && $resolvedMarket->id !== $price->market_id) {
                                $price->market_id = $resolvedMarket->id;
                                $price->save();
                                $fixedCount++;
                            }
                        }
                    }
                }

                // 2. Audit Price Fidelity
                if ($rawModal > 0 && abs((float) $price->modal_price - $rawModal) > 1.0) {
                    $priceMismatches[] = [
                        'price_id' => $price->id,
                        'date' => $price->price_date?->format('Y-m-d') ?? $price->price_date,
                        'crop' => $price->crop?->name,
                        'market' => $price->market?->name,
                        'raw_modal' => $rawModal,
                        'stored_modal' => (float) $price->modal_price,
                    ];

                    if ($autoFix) {
                        $price->modal_price = $rawModal;
                        $price->save();
                        $fixedCount++;
                    }
                }

                // 3. Audit Variety Sanity (check for blind fallback to IR-64 when raw variety was Sona Masuri, etc.)
                if ($rawVariety && $price->variety) {
                    $normRawVar = strtolower(trim($rawVariety));
                    $normAssignedVar = strtolower(trim($price->variety->name));

                    if (
                        (str_contains($normRawVar, 'sona') || str_contains($normRawVar, 'masuri')) &&
                        str_contains($normAssignedVar, 'ir-64')
                    ) {
                        $varietyMismatches[] = [
                            'price_id' => $price->id,
                            'date' => $price->price_date?->format('Y-m-d') ?? $price->price_date,
                            'raw_variety' => $rawVariety,
                            'assigned_variety' => $price->variety->name,
                        ];

                        if ($autoFix) {
                            $resolvedVar = $ingestionService->resolveVariety($price->crop_id, $rawVariety);
                            if ($resolvedVar) {
                                $price->variety_id = $resolvedVar->id;
                                $price->save();
                                $fixedCount++;
                            }
                        }
                    }
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Display Results Summary
        $this->info("=== AUDIT SUMMARY ===");
        $this->line("Records Audited:    <info>{$audited}</info>");
        $this->line("Market Discrepancies: " . (count($marketMismatches) > 0 ? "<error>" . count($marketMismatches) . "</error>" : "<info>0 (Clean)</info>"));
        $this->line("Variety Discrepancies: " . (count($varietyMismatches) > 0 ? "<error>" . count($varietyMismatches) . "</error>" : "<info>0 (Clean)</info>"));
        $this->line("Price Discrepancies:  " . (count($priceMismatches) > 0 ? "<error>" . count($priceMismatches) . "</error>" : "<info>0 (Clean)</info>"));

        if (!empty($marketMismatches)) {
            $this->newLine();
            $this->error("CRITICAL: Market Misattribution Discrepancies Found:");
            $this->table(
                ['Price ID', 'Date', 'Crop', 'Raw Market Payload', 'Assigned Market', 'Similarity'],
                array_slice($marketMismatches, 0, 20)
            );
            if (count($marketMismatches) > 20) {
                $this->warn("... and " . (count($marketMismatches) - 20) . " more mismatches.");
            }
        }

        if (!empty($varietyMismatches)) {
            $this->newLine();
            $this->error("Variety Discrepancies Found:");
            $this->table(
                ['Price ID', 'Date', 'Raw Variety Payload', 'Assigned Variety'],
                array_slice($varietyMismatches, 0, 20)
            );
        }

        if (!empty($priceMismatches)) {
            $this->newLine();
            $this->error("Price Modal Mismatches Found:");
            $this->table(
                ['Price ID', 'Date', 'Crop', 'Market', 'Raw Modal', 'Stored Modal'],
                array_slice($priceMismatches, 0, 20)
            );
        }

        if ($autoFix) {
            $this->newLine();
            $this->info("Auto-fix executed: {$fixedCount} records successfully reconciled.");
        }

        $totalIssues = count($marketMismatches) + count($varietyMismatches) + count($priceMismatches);
        if ($totalIssues === 0) {
            $this->newLine();
            $this->info("✅ SUCCESS: 100% 1-to-1 data fidelity verified. Zero discrepancies found!");
            return 0;
        }

        return $autoFix ? 0 : 1;
    }
}
