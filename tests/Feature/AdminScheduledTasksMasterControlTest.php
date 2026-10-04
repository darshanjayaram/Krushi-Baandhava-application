<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminScheduledTasksMasterControlTest extends TestCase
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

    public function test_guest_cannot_toggle_scheduled_cron_tasks(): void
    {
        $response = $this->post('/admin/scheduler/toggle-task', [
            'task' => 'forecasting',
            'enabled' => false,
        ]);

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_toggle_scheduled_task_via_ajax(): void
    {
        // Set initial state
        SystemSetting::set('cron_task_forecasting', 'true', 'boolean');

        // Toggle to paused
        $response = $this->actingAs($this->admin)->postJson('/admin/scheduler/toggle-task', [
            'task' => 'forecasting',
            'enabled' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'task' => 'forecasting',
                'enabled' => false,
            ]);

        $this->assertFalse((bool) SystemSetting::get('cron_task_forecasting', true));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'toggle_scheduled_cron_task',
            'user_id' => $this->admin->id,
        ]);

        // Toggle back to active
        $response2 = $this->actingAs($this->admin)->postJson('/admin/scheduler/toggle-task', [
            'task' => 'forecasting',
            'enabled' => true,
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'task' => 'forecasting',
                'enabled' => true,
            ]);

        $this->assertTrue((bool) SystemSetting::get('cron_task_forecasting', false));
    }

    public function test_toggle_task_validates_task_name(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/scheduler/toggle-task', [
            'task' => 'invalid_task_key',
            'enabled' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['task']);
    }

    public function test_admin_can_bulk_update_scheduled_tasks_from_drawer(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/datasources/update-schedule-timings', [
            'morning_time' => '06:30',
            'evening_time' => '19:00',
            'afternoon_time' => '13:00',
            'operating_days' => 'mon_sat',
            'enrolled_sources_submitted' => '1',
            'scheduled_tasks_submitted' => '1',
            'scheduled_tasks' => [
                'mandi_prices',
                'weather_sync',
                // intentionally omit analytics_stats, forecasting, retention_pruning, data_integrity
            ],
        ]);

        $response->assertSessionHas('success');

        $this->assertTrue((bool) SystemSetting::get('cron_task_mandi_prices', false));
        $this->assertTrue((bool) SystemSetting::get('cron_task_weather_sync', false));
        $this->assertFalse((bool) SystemSetting::get('cron_task_analytics_stats', true));
        $this->assertFalse((bool) SystemSetting::get('cron_task_forecasting', true));
        $this->assertFalse((bool) SystemSetting::get('cron_task_retention_pruning', true));
        $this->assertFalse((bool) SystemSetting::get('cron_task_data_integrity', true));
    }

    public function test_dashboard_displays_all_scheduled_tasks_with_master_switches(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Tasks Handled Automatically by this Cron');
        $response->assertSee('Mandi Market Prices Ingestion');
        $response->assertSee('Hyperlocal Weather Advisories');
        $response->assertSee('Historical Analytics & Seasonality');
        $response->assertSee('Price Forecasting Engine');
        $response->assertSee('1-Year Rolling Retention Pruner');
        $response->assertSee('Data Integrity Auditor');
        $response->assertSee('cron-task-btn');
    }

    public function test_console_scheduler_respects_task_filters(): void
    {
        // Pause forecasting
        SystemSetting::set('cron_task_forecasting', 'false', 'boolean');
        SystemSetting::set('cron_task_mandi_prices', 'true', 'boolean');

        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        // Find the forecasting event
        $forecastEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'krushi:generate-forecasts');
        });

        $this->assertNotNull($forecastEvent, 'krushi:generate-forecasts should be defined in schedule');

        // Execute the filters on the event: when paused, filters must return false
        $filters = (fn () => $this->filters)->call($forecastEvent);
        $filtersPass = true;
        foreach ($filters as $filter) {
            if (!$filter()) {
                $filtersPass = false;
                break;
            }
        }
        $this->assertFalse($filtersPass, 'Forecast event filter should evaluate to false when paused');

        // Find the mandi prices event
        $mandiEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'krushi:sync-market-prices');
        });

        $this->assertNotNull($mandiEvent, 'krushi:sync-market-prices should be defined in schedule');

        $mandiFilters = (fn () => $this->filters)->call($mandiEvent);
        $mandiFiltersPass = true;
        foreach ($mandiFilters as $filter) {
            if (!$filter()) {
                $mandiFiltersPass = false;
                break;
            }
        }
        $this->assertTrue($mandiFiltersPass, 'Mandi event filter should evaluate to true when enabled');
    }
}
