<?php

namespace Tests\Feature;

use App\Models\Crop;
use Tests\TestCase;

class FarmerWhereToSellTest extends TestCase
{
    public function test_farmer_can_view_where_to_sell_simulator(): void
    {
        $response = $this->get(route('farmer.decision.where-to-sell'));

        $response->assertStatus(200);
        $response->assertSee('Where to Sell', false);
        $response->assertSee('ಎಲ್ಲಿ ಮಾರಾಟ ಮಾಡಬೇಕು');
        $response->assertSee('ಕಟಾವು ಪ್ರಮಾಣ');
        $response->assertSee('ಸಾರಿಗೆ ವಾಹನ');
    }

    public function test_farmer_can_simulate_with_crop_quantity_and_vehicle(): void
    {
        $crop = Crop::where('is_active', true)->firstOrFail();

        $response = $this->get(route('farmer.decision.where-to-sell', [
            'crop' => $crop->slug,
            'quantity' => 20,
            'vehicle' => 'auto',
            'sort' => 'price_desc',
        ]));

        $response->assertStatus(200);
        $response->assertSee('20');
        $response->assertSee($crop->name);
    }

    public function test_api_decision_where_to_sell_returns_structured_json(): void
    {
        $crop = Crop::where('is_active', true)->firstOrFail();

        $response = $this->getJson(route('api.v1.decision.where-to-sell', [
            'crop' => $crop->slug,
            'quantity' => 10,
            'vehicle' => 'pickup',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'crop' => ['id', 'name', 'slug'],
            'origin' => ['latitude', 'longitude', 'name'],
            'quantity_quintals',
            'vehicle' => ['key', 'name_en', 'rate_per_km'],
            'markets_count',
            'markets',
        ]);
    }

    public function test_api_decision_validates_required_crop(): void
    {
        $response = $this->getJson(route('api.v1.decision.where-to-sell'));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Crop ID or slug is required.',
        ]);
    }

    public function test_crop_show_page_renders_quick_chips_and_where_to_sell_callout(): void
    {
        $crop = Crop::where('is_active', true)->firstOrFail();

        $response = $this->get(route('farmer.crops.show', $crop->slug));

        $response->assertStatus(200);
        $response->assertSee('Where to Sell', false);
        $response->assertSee('ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ');
    }
}
