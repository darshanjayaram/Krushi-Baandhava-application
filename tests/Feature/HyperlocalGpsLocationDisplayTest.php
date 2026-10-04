<?php

namespace Tests\Feature;

use App\Models\District;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HyperlocalGpsLocationDisplayTest extends TestCase
{
    use DatabaseTransactions;

    protected District $bengaluruDistrict;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bengaluruDistrict = District::where('name', 'Bengaluru Urban')->first()
            ?? District::firstOrCreate(
                ['name' => 'Bengaluru Urban'],
                ['code' => 'KA_BEN', 'name_kn' => 'ಬೆಂಗಳೂರು ನಗರ', 'latitude' => 12.9716, 'longitude' => 77.5946, 'is_active' => true]
            );
    }

    public function test_homepage_shows_kannada_locality_when_available(): void
    {
        // 1. English mode: shows Kothanur
        $responseEn = $this->withSession(['locale' => 'en'])->withCookies([
            'locale' => 'en',
            'selected_district_id' => $this->bengaluruDistrict->id,
            'selected_local_area' => 'Kothanur',
            'selected_local_area_kn' => 'ಕೊತ್ತನೂರು',
        ])->get('/?lang=en');

        $responseEn->assertStatus(200);
        $responseEn->assertSee('Kothanur');

        // 2. Kannada mode: shows ಕೊತ್ತನೂರು
        $responseKn = $this->withSession(['locale' => 'kn'])->withCookies([
            'locale' => 'kn',
            'selected_district_id' => $this->bengaluruDistrict->id,
            'selected_local_area' => 'Kothanur',
            'selected_local_area_kn' => 'ಕೊತ್ತನೂರು',
        ])->get('/?lang=kn');

        $responseKn->assertStatus(200);
        $responseKn->assertSee('ಕೊತ್ತನೂರು');
    }

    public function test_homepage_shows_english_locality_in_kannada_mode_when_kannada_name_is_missing(): void
    {
        // When local_area_kn is empty/missing
        $responseKn = $this->withSession(['locale' => 'kn'])->withCookies([
            'locale' => 'kn',
            'selected_district_id' => $this->bengaluruDistrict->id,
            'selected_local_area' => 'Kothanur',
            'selected_local_area_kn' => '',
        ])->get('/?lang=kn');

        $responseKn->assertStatus(200);
        $responseKn->assertSee('Kothanur');
    }

    public function test_homepage_preserves_english_locality_when_kannada_is_broad_bengaluru_city(): void
    {
        // Even if old cookie stored local_area_kn as 'ಬೆಂಗಳೂರು', it must show 'Kothanur' in Kannada mode
        $responseKn = $this->withSession(['locale' => 'kn'])->withCookies([
            'locale' => 'kn',
            'selected_district_id' => $this->bengaluruDistrict->id,
            'selected_local_area' => 'Kothanur',
            'selected_local_area_kn' => 'ಬೆಂಗಳೂರು',
        ])->get('/?lang=kn');

        $responseKn->assertStatus(200);
        $responseKn->assertSee('Kothanur');
        $responseKn->assertDontSee('ಬೆಂಗಳೂರು (ಬೆಂಗಳೂರು');
    }

    public function test_set_location_sanitizes_broad_city_in_local_area_kn(): void
    {
        $response = $this->postJson('/set-location', [
            'district_id' => $this->bengaluruDistrict->id,
            'local_area' => 'Kothanur',
            'local_area_kn' => 'ಬೆಂಗಳೂರು', // broad city passed by accident
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'local_area' => 'Kothanur',
            'local_area_kn' => 'Kothanur', // sanitized to Kothanur
        ]);

        $this->assertEquals('Kothanur', session('selected_local_area'));
        $this->assertEquals('Kothanur', session('selected_local_area_kn'));
    }

    public function test_reverse_geocode_preserves_specific_locality_for_both_languages(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*accept-language=en*' => Http::response([
                'address' => [
                    'suburb' => 'Kothanur',
                    'city' => 'Bengaluru',
                    'state_district' => 'Bengaluru Urban',
                ]
            ], 200),
            'https://nominatim.openstreetmap.org/reverse*accept-language=kn*' => Http::response([
                'address' => [
                    'city' => 'ಬೆಂಗಳೂರು', // missing suburb in Kannada
                    'state_district' => 'ಬೆಂಗಳೂರು ನಗರ',
                ]
            ], 200),
        ]);

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget("krushi_rev_geo_12.876_77.585");

        $response = $this->getJson('/reverse-geocode?lat=12.876&lon=77.585');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'local_area' => 'Kothanur',
                    'local_area_kn' => 'Kothanur', // falls back to Kothanur, not ಬೆಂಗಳೂರು
                ]
            ]);
    }
}
