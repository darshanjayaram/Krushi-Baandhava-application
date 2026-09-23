<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\District;
use App\Models\FeatureFlag;
use App\Models\Market;
use App\Models\SystemSetting;
use Tests\TestCase;

class MasterDataModelTest extends TestCase
{
    /**
     * Test geographic models and relations.
     */
    public function test_district_has_markets_and_taluks(): void
    {
        $shivamogga = District::where('name', 'Shivamogga')->first();

        $this->assertNotNull($shivamogga);
        $this->assertTrue($shivamogga->taluks()->count() > 0);
        $this->assertTrue($shivamogga->markets()->count() > 0);
    }

    /**
     * Test crop has varieties relation.
     */
    public function test_crop_has_varieties(): void
    {
        $arecanut = Crop::where('slug', 'arecanut')->first();

        $this->assertNotNull($arecanut);
        $this->assertTrue($arecanut->varieties()->count() > 0);
    }

    /**
     * Test FeatureFlag cache lookup.
     */
    public function test_feature_flag_is_enabled(): void
    {
        $this->assertTrue(FeatureFlag::isEnabled('market_prices'));
        $this->assertFalse(FeatureFlag::isEnabled('notifications'));
    }

    /**
     * Test SystemSetting get helper.
     */
    public function test_system_setting_get(): void
    {
        $appName = SystemSetting::get('application_name');
        $this->assertEquals('Krushi Baandhava', $appName);
    }

    /**
     * Test Market nearby Haversine query.
     */
    public function test_market_nearby_scope(): void
    {
        // Coordinates near Shivamogga city (13.9299, 75.5681)
        $nearbyMarkets = Market::nearby(13.9299, 75.5681, 60)->get();

        $this->assertTrue($nearbyMarkets->isNotEmpty());
        $this->assertNotNull($nearbyMarkets->first()->distance);
        $this->assertLessThanOrEqual(60, $nearbyMarkets->first()->distance);
    }
}
