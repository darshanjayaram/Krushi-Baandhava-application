<?php

namespace App\Jobs;

use App\Services\Weather\WeatherSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWeatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times job may be attempted.
     */
    public int $tries = 2;

    /**
     * Timeout in seconds before job fails.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public bool $force = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WeatherSyncService $service): void
    {
        Log::info('SyncWeatherJob started: running weather sync across all districts');
        
        $stats = $service->syncAllDistricts($this->force);
        
        Log::info('SyncWeatherJob finished successfully', $stats);
    }
}
