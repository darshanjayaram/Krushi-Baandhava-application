<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\PriceDailyStatistic;
use App\Models\PriceMonthlyStatistic;
use App\Models\State;
use App\Services\Analytics\HistoricalAnalyticsService;
use Carbon\Carbon;
use Tests\TestCase;

class HistoricalAnalyticsServiceTest extends TestCase
{
    protected State $karnataka;
    protected District $district;
    protected Market $market;
    protected Crop $crop;
    protected HistoricalAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->karnataka = State::where('code', 'KA')->firstOrFail();
        $this->district = District::where('state_id', $this->karnataka->id)->firstOrFail();
        $this->market = Market::where('district_id', $this->district->id)->firstOrFail();
        $this->crop = Crop::where('slug', 'arecanut')->firstOrFail();

        $this->analyticsService = new HistoricalAnalyticsService();
    }

    public function test_compute_daily_statistics_aggregates_and_persists(): void
    {
        $testDate = '2026-08-15';

        // Clear any existing test date records
        MarketPrice::where('crop_id', $this->crop->id)->where('price_date', $testDate)->delete();
        PriceDailyStatistic::where('crop_id', $this->crop->id)->where('record_date', $testDate)->delete();

        MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'price_date' => $testDate,
            'min_price' => 45000,
            'max_price' => 52000,
            'modal_price' => 50000,
            'arrival_quantity' => 120.5,
            'unit' => 'Quintal',
        ]);

        $saved = $this->analyticsService->computeDailyStatistics($testDate, $this->crop->id);

        $this->assertGreaterThan(0, $saved);
        $this->assertDatabaseHas('price_daily_statistics', [
            'crop_id' => $this->crop->id,
            'record_date' => $testDate,
            'avg_modal_price' => 50000.00,
        ]);
    }

    public function test_compute_monthly_statistics_and_seasonal_index(): void
    {
        $year = 2026;
        $month = 7;
        $date = "{$year}-0{$month}-10";

        // Clean existing
        MarketPrice::where('crop_id', $this->crop->id)->where('price_date', $date)->delete();
        PriceMonthlyStatistic::where('crop_id', $this->crop->id)->where('year', $year)->where('month', $month)->delete();

        MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'price_date' => $date,
            'min_price' => 40000,
            'max_price' => 46000,
            'modal_price' => 44000,
            'arrival_quantity' => 50,
            'unit' => 'Quintal',
        ]);

        $saved = $this->analyticsService->computeMonthlyStatistics($year, $month, $this->crop->id);

        $this->assertGreaterThan(0, $saved);
        $this->assertDatabaseHas('price_monthly_statistics', [
            'crop_id' => $this->crop->id,
            'market_id' => null, // State-level aggregate
            'year' => $year,
            'month' => $month,
            'avg_modal_price' => 44000.00,
        ]);

        $stat = PriceMonthlyStatistic::where('crop_id', $this->crop->id)->whereNull('market_id')->where('year', $year)->where('month', $month)->first();
        $this->assertNotNull($stat);
        $this->assertNotNull($stat->seasonal_index);
    }

    public function test_daily_trends_timeseries_structure(): void
    {
        $date1 = Carbon::today()->subDays(2)->toDateString();
        $date2 = Carbon::today()->subDays(1)->toDateString();

        MarketPrice::where('crop_id', $this->crop->id)->whereIn('price_date', [$date1, $date2])->delete();
        PriceDailyStatistic::where('crop_id', $this->crop->id)->delete();

        MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'price_date' => $date1,
            'min_price' => 48000,
            'max_price' => 50000,
            'modal_price' => 49000,
            'arrival_quantity' => 10,
            'unit' => 'Quintal',
        ]);

        MarketPrice::create([
            'crop_id' => $this->crop->id,
            'market_id' => $this->market->id,
            'price_date' => $date2,
            'min_price' => 50000,
            'max_price' => 52000,
            'modal_price' => 51000,
            'arrival_quantity' => 15,
            'unit' => 'Quintal',
        ]);

        $trends = $this->analyticsService->getDailyTrends($this->crop->id, $this->market->id, 7);

        $this->assertTrue($trends['has_data']);
        $this->assertIsArray($trends['labels']);
        $this->assertIsArray($trends['modal_prices']);
        $this->assertContains(49000.0, $trends['modal_prices']);
        $this->assertContains(51000.0, $trends['modal_prices']);
    }

    public function test_seasonal_analysis_identifies_best_months(): void
    {
        $seasonal = $this->analyticsService->getSeasonalAnalysis($this->crop->id);

        $this->assertIsArray($seasonal);
        $this->assertCount(12, $seasonal['monthly_profile']);
        $this->assertArrayHasKey('best_months', $seasonal);
        $this->assertArrayHasKey('annual_baseline', $seasonal);
    }

    public function test_statistical_summary_handles_empty_and_active_data(): void
    {
        // 1. Unused crop ID
        $emptySummary = $this->analyticsService->getStatisticalSummary(999999, null, 30);
        $this->assertEquals(0, $emptySummary['avg_price']);
        $this->assertEquals(0, $emptySummary['observations_count']);

        // 2. Active data with current crop
        $summary = $this->analyticsService->getStatisticalSummary($this->crop->id, null, 30);
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('volatility_rating', $summary);
        $this->assertArrayHasKey('trend_direction', $summary);
    }
}
