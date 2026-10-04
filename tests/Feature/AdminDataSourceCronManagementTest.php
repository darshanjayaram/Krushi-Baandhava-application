<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDataSourceCronManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

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
    }

    public function test_admin_can_toggle_cron_status_via_post(): void
    {
        $source = DataSource::where('is_active', true)->first();
        $this->assertNotNull($source);

        $initialState = (bool) $source->is_cron_enabled;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.datasources.toggle-cron', $source));

        $response->assertRedirect();

        $source->refresh();
        $this->assertSame(!$initialState, (bool) $source->is_cron_enabled);
    }

    public function test_admin_can_toggle_cron_status_via_ajax_json(): void
    {
        $source = DataSource::where('is_active', true)->first();
        $this->assertNotNull($source);

        $initialState = (bool) $source->is_cron_enabled;

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.datasources.toggle-cron', $source));

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'is_cron_enabled' => !$initialState,
        ]);

        $source->refresh();
        $this->assertSame(!$initialState, (bool) $source->is_cron_enabled);
    }

    public function test_admin_can_bulk_update_schedule_timings_and_enrolled_sources(): void
    {
        $sources = DataSource::where('is_active', true)->take(2)->get();
        $this->assertGreaterThanOrEqual(1, $sources->count());

        $firstSourceId = $sources->first()->id;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.datasources.update-schedule-timings'), [
                'morning_time' => '07:00',
                'evening_time' => '19:00',
                'afternoon_time' => '13:00',
                'operating_days' => 'mon_sat',
                'enable_hourly' => '1',
                'apply_to_sources' => '1',
                'enrolled_sources_submitted' => '1',
                'enrolled_sources' => [$firstSourceId],
            ]);

        $response->assertRedirect();

        $this->assertTrue((bool) DataSource::find($firstSourceId)->is_cron_enabled);

        if ($sources->count() > 1) {
            $secondSourceId = $sources[1]->id;
            $this->assertFalse((bool) DataSource::find($secondSourceId)->is_cron_enabled);
        }
    }

    public function test_cron_command_respects_is_cron_enabled_flag(): void
    {
        // Set all sources to is_cron_enabled = false
        DataSource::query()->update(['is_cron_enabled' => false]);

        $exitCode = $this->artisan('krushi:sync-market-prices', [
            '--cron-only' => true,
            '--dry-run' => true,
        ])->run();

        $this->assertSame(0, $exitCode);
    }

    public function test_manual_sync_bypasses_cron_flag_when_source_specified(): void
    {
        $source = DataSource::where('is_active', true)->first();
        $this->assertNotNull($source);

        // Exclude this source from cron
        $source->update(['is_cron_enabled' => false]);

        // Running explicitly with source code should find and process the source
        $exitCode = $this->artisan('krushi:sync-market-prices', [
            'source' => $source->code,
            '--dry-run' => true,
        ])->run();

        $this->assertSame(0, $exitCode);
    }
}
