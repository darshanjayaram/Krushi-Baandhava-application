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
        // 1. A crop with fewer than 3 months of data returns is_sufficient = false and empty best_months
        $sparseCrop = Crop::create([
            'name' => 'Sparse Seasonal Test Crop',
            'name_kn' => 'ಸ್ಪಾರ್ಸ್ ಬೆಳೆ',
            'slug' => 'sparse-seasonal-' . uniqid(),
            'category_id' => $this->crop->category_id,
            'is_active' => true,
        ]);

        MarketPrice::create([
            'crop_id' => $sparseCrop->id,
            'market_id' => $this->market->id,
            'price_date' => '2026-09-01',
            'min_price' => 2000,
            'max_price' => 2200,
            'modal_price' => 2100,
            'unit' => 'Quintal',
        ]);

        $sparseSeasonal = $this->analyticsService->getSeasonalAnalysis($sparseCrop->id);
        $this->assertFalse($sparseSeasonal['is_sufficient']);
        $this->assertEmpty($sparseSeasonal['best_months']);
        $this->assertCount(12, $sparseSeasonal['monthly_profile']);
        $this->assertStringContainsString('ಕನಿಷ್ಠ 2', $sparseSeasonal['message_kn']);

        // 2. A crop with 3 or more distinct months computes genuine seasonality without synthetic curves
        MarketPrice::create([
            'crop_id' => $sparseCrop->id,
            'market_id' => $this->market->id,
            'price_date' => '2026-01-15',
            'min_price' => 1800,
            'max_price' => 1900,
            'modal_price' => 1850,
            'unit' => 'Quintal',
        ]);

        MarketPrice::create([
            'crop_id' => $sparseCrop->id,
            'market_id' => $this->market->id,
            'price_date' => '2026-05-15',
            'min_price' => 3000,
            'max_price' => 3200,
            'modal_price' => 3100,
            'unit' => 'Quintal',
        ]);

        $multiMonthSeasonal = $this->analyticsService->getSeasonalAnalysis($sparseCrop->id);
        $this->assertTrue($multiMonthSeasonal['is_sufficient']);
        $this->assertNotEmpty($multiMonthSeasonal['best_months']);
        $this->assertCount(12, $multiMonthSeasonal['monthly_profile']);
        $this->assertStringContainsString('ಮೇ', $multiMonthSeasonal['best_months'][0]['month_name_kn']); // May (3100) is peak!

        // Clean up
        MarketPrice::where('crop_id', $sparseCrop->id)->delete();
        $sparseCrop->delete();
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
