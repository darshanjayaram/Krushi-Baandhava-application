<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\DataSource;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncMarketPricesCommandTest extends TestCase
{
    protected User $admin;
    protected DataSource $dataSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@krushibaandhava.org'],
            [
                'name' => 'Data Admin',
                'password' => bcrypt('password123'),
                'role' => 'super_admin',
                'preferred_language' => 'kn',
            ]
        );

        $this->dataSource = DataSource::where('code', 'data_gov_mandi')->firstOrFail();
    }

    public function test_sync_command_runs_successfully_for_datagov_source(): void
    {
        $exitCode = Artisan::call('krushi:sync-market-prices', [
            'source' => 'data_gov_mandi',
        ]);

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('KRUSHI BAANDHAVA', $output);
        $this->assertStringContainsString('data_gov_mandi', $output);
    }

    public function test_sync_command_dry_run_does_not_mutate_market_prices(): void
    {
        $beforeCount = MarketPrice::count();

        $exitCode = Artisan::call('krushi:sync-market-prices', [
            'source' => 'data_gov_mandi',
            '--dry-run' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals($beforeCount, MarketPrice::count());
        $this->assertStringContainsString('DRY RUN', Artisan::output());
    }

    public function test_guest_cannot_access_market_prices_admin(): void
    {
        $response = $this->get('/admin/prices');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_market_prices_index(): void
    {
        // Ensure at least one price exists
        Artisan::call('krushi:sync-market-prices', [
            'source' => 'data_gov_mandi',
            '--force' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/prices');

        $response->assertStatus(200);
        $response->assertSee('Daily Market Prices', false);
        $response->assertSee('Total Canonical Records', false);
        $response->assertSee('Canonical Prices', false);
    }

    public function test_admin_can_filter_market_prices_by_crop(): void
    {
        $crop = Crop::where('slug', 'arecanut')->firstOrFail();

        $response = $this->actingAs($this->admin)->get('/admin/prices?crop_id=' . $crop->id);

        $response->assertStatus(200);
        $response->assertSee($crop->name);
    }

    public function test_admin_can_trigger_prices_sync_via_post(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/prices/sync', [
                'data_source_id' => $this->dataSource->id,
            ]);

        $response->assertRedirect(route('admin.prices.index'));
        $response->assertSessionHas('success');
    }
}
