<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CropMarketSelectionFreshnessTest extends TestCase
{
    use DatabaseTransactions;

    protected District $bengaluruDistrict;
    protected Crop $blackPepper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bengaluruDistrict = District::where('name', 'Bengaluru Urban')->first()
            ?? District::firstOrCreate(
                ['name' => 'Bengaluru Urban'],
                ['code' => 'KA_BEN', 'name_kn' => 'ಬೆಂಗಳೂರು ನಗರ', 'latitude' => 12.9716, 'longitude' => 77.5946, 'is_active' => true]
            );

        $this->blackPepper = Crop::where('slug', 'black-pepper')->orWhere('name', 'Black Pepper')->firstOrFail();
    }

    public function test_crop_detail_falls_back_to_active_market_when_requested_market_has_no_fresh_trades(): void
    {
        // When market=BENGALURU is passed, but Bengaluru has no fresh trades for Black Pepper:
        $response = $this->withCookies([
            'selected_district_id' => $this->bengaluruDistrict->id,
        ])->get('/crops/' . $this->blackPepper->slug . '?market=BENGALURU');

        $response->assertStatus(200);

        $data = $response->original->getData();

        // 1. Selected market must NOT be Bengaluru
        $this->assertNotNull($data['selectedMarket']);
        $this->assertNotEquals('Bengaluru', $data['selectedMarket']->name);

        // 2. Selected market must be in availableMarkets
        $availIds = collect($data['availableMarkets'])->pluck('id')->all();
        $this->assertContains($data['selectedMarket']->id, $availIds);

        // 3. Grades list must NOT be empty
        $this->assertNotEmpty($data['gradesList']);

        // 4. Displayed market name must match active price item market
        $mkt = $data['activePriceItem']->market;
        $this->assertContains($data['displayMarketName'], [$mkt->name, $mkt->name_kn]);
    }

    public function test_homepage_does_not_select_stale_district_price_for_crop_with_no_recent_local_trades(): void
    {
        $response = $this->withCookies([
            'selected_district_id' => $this->bengaluruDistrict->id,
        ])->get('/');

        $response->assertStatus(200);

        $data = $response->original->getData();
        $sortedPrices = collect($data['sortedPrices']);
        $pepperPrice = $sortedPrices->firstWhere('crop_id', $this->blackPepper->id);

        $this->assertNotNull($pepperPrice);
        // It must NOT be a stale local price for Bengaluru from August
        $this->assertEquals('Benchmark', $pepperPrice->reliability_badge);
        $this->assertNotEquals('Bengaluru', $pepperPrice->market->name);
    }

    public function test_homepage_list_view_does_not_leak_mover_variable_into_crop_links(): void
    {
        $response = $this->withCookies([
            'selected_district_id' => $this->bengaluruDistrict->id,
        ])->get('/');

        $response->assertStatus(200);

        // Confirm no crop link in HTML contains mover market when price has its own market
        $content = $response->getContent();
        $this->assertStringNotContainsString('?market=Madikeri+Market" class="flex items-center gap-3 min-w-0 flex-1">', $content);
    }
}
