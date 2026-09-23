<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncMarketPricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'krushi:sync-market-prices
                            {source? : Code of the specific data source (e.g. data_gov_mandi, agmarknet)}
                            {--date= : Specific target date to sync (YYYY-MM-DD), defaults to today}
                            {--force : Force sync even if raw payload checksum already exists}
                            {--dry-run : Ingest and validate raw payloads without upserting to canonical prices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ingest and canonicalize daily market prices from external data sources (data.gov.in, Agmarknet, Commodity Boards)';

    public function __construct(
        protected MarketPriceIngestionService $ingestionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceCode = $this->argument('source');
        $dateParam = $this->option('date');
        $targetDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();
        $isForced = (bool) $this->option('force');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("============================================================");
        $this->info("   KRUSHI BAANDHAVA (ಕೃಷಿ ಬಾಂಧವ) - Market Price Ingestion   ");
        $this->info("============================================================");
        $this->line("Target Date: <comment>{$targetDate->toDateString()}</comment>");
        $this->line("Mode:        " . ($isDryRun ? '<fg=yellow;options=bold>DRY RUN (No Canonical Writes)</>' : '<fg=green;options=bold>LIVE SYNC</>'));

        $query = DataSource::query();

        if ($sourceCode) {
            $query->where('code', $sourceCode);
        } else {
            $query->where('is_active', true);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn("No data sources found" . ($sourceCode ? " matching code '{$sourceCode}'." : "."));
            return self::SUCCESS;
        }

        $this->line("Found <info>{$sources->count()}</info> data source(s) to process.\n");

        $overallReceived = 0;
        $overallInserted = 0;
        $overallUpdated = 0;
        $overallDuplicates = 0;
        $overallRejected = 0;

        foreach ($sources as $source) {
            $this->info("▶ Ingesting from: <fg=cyan>{$source->name}</> [{$source->code}]");

            $result = $this->ingestionService->ingest($source, [
                'force' => $isForced,
                'dry_run' => $isDryRun,
                'filters' => [
                    'date' => $targetDate->toDateString(),
                ],
            ]);

            $statusColor = match ($result['status']) {
                'success' => 'green',
                'partial' => 'yellow',
                default => 'red',
            };

            $this->line("  Status:        <fg={$statusColor};options=bold>" . strtoupper($result['status']) . "</>");
            $this->line("  Received:      {$result['received']}");
            $this->line("  New Inserted:  {$result['inserted']}");
            $this->line("  Updated:       {$result['updated']}");
            $this->line("  Duplicates:    {$result['duplicate']}");
            $this->line("  Rejected:      {$result['rejected']}");
            $this->line("  Duration:      {$result['duration_ms']} ms");

            if (! empty($result['errors'])) {
                $this->warn("  Errors / Warnings:");
                foreach (array_slice($result['errors'], 0, 5) as $err) {
                    $this->line("    • {$err}");
                }
                if (count($result['errors']) > 5) {
                    $this->line("    • ... and " . (count($result['errors']) - 5) . " more.");
                }
            }

            $overallReceived += $result['received'];
            $overallInserted += $result['inserted'];
            $overallUpdated += $result['updated'];
            $overallDuplicates += $result['duplicate'];
            $overallRejected += $result['rejected'];

            $this->newLine();
        }

        $this->table(
            ['Metric', 'Total Across Sources'],
            [
                ['Total Records Received', $overallReceived],
                ['New Canonical Inserted', $overallInserted],
                ['Existing Canonical Updated', $overallUpdated],
                ['Duplicate Payloads Skipped', $overallDuplicates],
                ['Invalid/Rejected Records', $overallRejected],
            ]
        );

        $this->info("Ingestion completed successfully at " . Carbon::now()->toTimeString() . ".");

        return self::SUCCESS;
    }
}
