<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminNotesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->farmer = User::where('role', User::ROLE_FARMER)->first()
            ?? User::factory()->create(['role' => User::ROLE_FARMER]);
    }

    public function test_guest_and_farmer_cannot_access_admin_notes(): void
    {
        $this->get(route('admin.notes.index'))->assertRedirect('/admin/login');
        $this->actingAs($this->farmer)->get(route('admin.notes.index'))->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_system_guidelines_and_notes(): void
    {
        SystemSetting::set('admin_operational_notes', 'Meeting with Shimoga APMC Secretary on Monday', 'string', 'operations');

        $response = $this->actingAs($this->admin)->get(route('admin.notes.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin Notes & System Guidelines', false);
        $response->assertSee('Preferred Crop Image Specifications');
        $response->assertSee('Crop Ingestion & Variety Mapping Rules', false);
        $response->assertSee('Mandi Naming & Single Canonical Town Consolidation', false);
        $response->assertSee('Dynamic 4-Day Freshness Window & Staleness Architecture', false);
        $response->assertSee('Cron Scheduling & Independent Source Controls', false);
        $response->assertSee('Bilingual Locality Resolution & Fallback Policy', false);
        $response->assertSee('Meeting with Shimoga APMC Secretary on Monday');
    }

    public function test_admin_can_update_operational_scratchpad(): void
    {
        $newNotes = 'Updated internal memo: Coffee auctions open from 9 AM at Madikeri & Sakleshpur.';

        $response = $this->actingAs($this->admin)->post(route('admin.notes.update'), [
            'admin_notes' => $newNotes,
        ]);

        $response->assertRedirect(route('admin.notes.index'));
        $response->assertSessionHas('success');

        $this->assertEquals($newNotes, SystemSetting::get('admin_operational_notes'));
    }
}
