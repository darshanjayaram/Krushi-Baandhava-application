<?php

namespace App\Jobs;

use App\Models\DataSource;
use App\Services\Ingestion\MarketPriceIngestionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMarketPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?string $dataSourceCode = null,
        public ?string $targetDate = null,
        public bool $isDryRun = false
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MarketPriceIngestionService $ingestionService): void
    {
        $targetDate = $this->targetDate ?? Carbon::today()->toDateString();
        Log::info("Starting SyncMarketPricesJob", [
            'dataSourceCode' => $this->dataSourceCode,
            'targetDate' => $targetDate,
            'isDryRun' => $this->isDryRun,
        ]);

        $query = DataSource::query()->where('is_active', true);
        if ($this->dataSourceCode) {
            $query->where('code', $this->dataSourceCode);
        }

        $sources = $query->get();

        foreach ($sources as $source) {
            try {
                $result = $ingestionService->ingest($source, [
                    'dry_run' => $this->isDryRun,
                    'filters' => [
                        'date' => $targetDate,
                    ],
                ]);

                Log::info("SyncMarketPricesJob finished for {$source->code}", $result);
            } catch (\Throwable $e) {
                Log::error("SyncMarketPricesJob failed for source {$source->code}: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }
    }
}
