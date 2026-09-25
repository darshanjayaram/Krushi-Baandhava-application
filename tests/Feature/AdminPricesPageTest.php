<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminPricesPageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    /**
     * Test admin can view daily market prices index page with metrics and table.
     */
    public function test_admin_can_view_daily_market_prices_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/prices');

        $response->assertStatus(200);
        $response->assertSee('Daily Market Prices', false);
        $response->assertSee('Total Canonical Records', false);
        $response->assertSee('Latest Date Records', false);
        $response->assertSee('Canonical Prices', false);
        $response->assertSee('Run Ingestion Sync', false);
    }

    /**
     * Test filtering prices by crop and search text.
     */
    public function test_admin_can_filter_market_prices(): void
    {
        $crop = Crop::first();
        $this->assertNotNull($crop);

        $response = $this->actingAs($this->admin)->get("/admin/prices?crop_id={$crop->id}&search={$crop->name}");

        $response->assertStatus(200);
        $response->assertSee($crop->name, false);
    }
}
