<?php

namespace App\Console\Commands;

use App\Services\Analytics\HistoricalAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ComputePriceStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'krushi:compute-statistics 
                            {--date= : Specific date to compute daily statistics (YYYY-MM-DD)}
                            {--backfill-days=0 : Number of previous days to backfill}
                            {--months : Compute monthly statistics and seasonal index}';

    /**
     * The console command description.
     */
    protected $description = 'Compute daily and monthly historical price statistics and seasonal indexes';

    /**
     * Execute the console command.
     */
    public function handle(HistoricalAnalyticsService $analyticsService): int
    {
        $this->info('Starting Historical Price Statistics Calculation...');

        $dateParam = $this->option('date');
        $backfillDays = (int) $this->option('backfill-days');

        if ($backfillDays > 0) {
            $this->info("Backfilling daily statistics for the past {$backfillDays} days...");
            $totalSaved = 0;
            for ($i = $backfillDays; $i >= 0; $i--) {
                $targetDate = Carbon::today()->subDays($i)->toDateString();
                $saved = $analyticsService->computeDailyStatistics($targetDate);
                $totalSaved += $saved;
                $this->line(" - {$targetDate}: {$saved} records");
            }
            $this->info("Completed daily backfill: {$totalSaved} total records created/updated.");
        } else {
            $targetDate = $dateParam ? Carbon::parse($dateParam)->toDateString() : Carbon::today()->toDateString();
            $this->info("Computing daily statistics for {$targetDate}...");
            $saved = $analyticsService->computeDailyStatistics($targetDate);
            $this->info("Daily statistics computed: {$saved} records saved/updated.");
        }

        if ($this->option('months') || $backfillDays > 0) {
            $this->info('Computing monthly aggregates and updating 5-year seasonal index...');
            $currentYear = (int) Carbon::now()->year;
            $currentMonth = (int) Carbon::now()->month;

            $monthlySaved = $analyticsService->computeMonthlyStatistics($currentYear, $currentMonth);
            $this->info("Monthly statistics computed: {$monthlySaved} records.");
            $this->info('Updated seasonal index across all active crops.');
        }

        $this->info('Historical analytics computation completed successfully!');

        return Command::SUCCESS;
    }
}
