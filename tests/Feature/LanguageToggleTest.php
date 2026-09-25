<?php

namespace Tests\Feature;

use Tests\TestCase;

class LanguageToggleTest extends TestCase
{
    /**
     * Test default locale is Kannada ('kn').
     */
    public function test_default_locale_is_kannada(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertEquals('kn', app()->getLocale());
        $response->assertCookie('locale', 'kn');
        $response->assertSee('ದರಗಳು');
    }

    /**
     * Test switching to English via /locale/en route.
     */
    public function test_switching_to_english_via_route(): void
    {
        $response = $this->get('/locale/en');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
        $response->assertCookie('locale', 'en');
    }

    /**
     * Test English locale renders English text on farmer home page.
     */
    public function test_english_locale_renders_english_ui_elements(): void
    {
        $response = $this->withSession(['locale' => 'en'])
                         ->withCookie('locale', 'en')
                         ->get('/');

        $response->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
        $response->assertSee("Today's Market Rates");
        $response->assertSee('Live Market Data');
        $response->assertSee('Rates');
        $response->assertSee('Schemes');
    }

    /**
     * Test switching back to Kannada via /locale/kn route.
     */
    public function test_switching_back_to_kannada(): void
    {
        $response = $this->withSession(['locale' => 'en'])
                         ->get('/locale/kn');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'kn');
        $response->assertCookie('locale', 'kn');
    }

    /**
     * Test unsupported/invalid locale defaults safely to 'kn'.
     */
    public function test_invalid_locale_defaults_to_kannada(): void
    {
        $response = $this->get('/locale/fr');

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'kn');
        $response->assertCookie('locale', 'kn');
    }

    /**
     * Test switching locale preserves referer URL for seamless browsing.
     */
    public function test_locale_switch_preserves_referer_url(): void
    {
        $referer = url('/crops');
        $response = $this->withHeaders(['referer' => $referer])
                         ->get('/locale/en');

        $response->assertRedirect($referer);
    }

    /**
     * Test API endpoint for setting locale.
     */
    public function test_api_set_locale_endpoint(): void
    {
        $response = $this->postJson('/api/v1/set-locale', ['locale' => 'en']);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'locale' => 'en',
                 ]);
    }

    /**
     * Test layout has notranslate meta and attributes to prevent browser translate bar.
     */
    public function test_layout_has_notranslate_meta_and_attributes(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="google" content="notranslate">', false);
        $response->assertSee('translate="no"', false);
        $response->assertSee('notranslate', false);
    }

    /**
     * Test English mode renders pure English varieties without Kannada parens.
     */
    public function test_english_mode_renders_pure_english_varieties_without_kannada(): void
    {
        $response = $this->withSession(['locale' => 'en'])
                         ->withCookie('locale', 'en')
                         ->get('/');

        $response->assertStatus(200);
        // Assert no Kannada script attached to English variety titles like (ಗಾರ್ಬಲ್ಡ್) or (ರಾಶಿ)
        $response->assertDontSee('(ಗಾರ್ಬಲ್ಡ್)', false);
        $response->assertDontSee('(ರಾಶಿ)', false);
        $response->assertDontSee('(ಇಂಡಾಫ್)', false);
        $response->assertDontSee('(ಮಧ್ಯಮ)', false);
        $response->assertDontSee('(ಸೋನಾ ಮಸೂರಿ)', false);
    }

    /**
     * Test crop detail page renders English labels and headers when English locale is active.
     */
    public function test_crop_show_page_renders_english_when_english_locale_active(): void
    {
        $crop = \App\Models\Crop::where('slug', 'arecanut')->first() ?? \App\Models\Crop::firstOrFail();

        $response = $this->withSession(['locale' => 'en'])
                         ->withCookie('locale', 'en')
                         ->get(route('farmer.crops.show', $crop->slug));

        $response->assertStatus(200);
        $response->assertSee('CURRENT PRICE');
        $response->assertSee("What's next", false);
        $response->assertSee('Price Forecast & Projections', false);
        $response->assertSee('Historical Price Trend');
        $response->assertSee('Best Months to Sell');
        $response->assertSee('Period High');
        $response->assertSee('Period Low');
        $response->assertSee('Period Average');
        $response->assertSee('Price Volatility');
        $response->assertSee('Net Profit Comparison');
        $response->assertSee('Mandi Price Comparison');
        $response->assertDontSee('ಇಂದಿನ ದರ', false);
        $response->assertDontSee('(ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ)', false);
    }

    /**
     * Test crop detail page renders pure Kannada labels and headers without English parens when Kannada locale is active.
     */
    public function test_crop_show_page_renders_kannada_when_kannada_locale_active(): void
    {
        $crop = \App\Models\Crop::where('slug', 'arecanut')->first() ?? \App\Models\Crop::firstOrFail();

        $response = $this->withSession(['locale' => 'kn'])
                         ->withCookie('locale', 'kn')
                         ->get(route('farmer.crops.show', $crop->slug));

        $response->assertStatus(200);
        $response->assertSee('ಇಂದಿನ ದರ', false);
        $response->assertDontSee('ಇಂದಿನ ದರ (CURRENT PRICE)', false);
        $response->assertSee('ಬೆಲೆ ಇತಿಹಾಸ & ಪ್ರವೃತ್ತಿ', false);
        $response->assertSee('ಮಾರಾಟಕ್ಕೆ ಉತ್ತಮ ತಿಂಗಳು', false);
        $response->assertSee('ಅವಧಿಯ ಗರಿಷ್ಠ', false);
        $response->assertDontSee('ಅವಧಿಯ ಗರಿಷ್ಠ (Period High)', false);
        $response->assertSee('ಅವಧಿಯ ಕನಿಷ್ಠ', false);
        $response->assertDontSee('ಅವಧಿಯ ಕನಿಷ್ಠ (Period Low)', false);
        $response->assertSee('ಅವಧಿಯ ಸರಾಸರಿ', false);
        $response->assertDontSee('ಅವಧಿಯ ಸರಾಸರಿ (Period Avg)', false);
        $response->assertSee('ಬೆಲೆ ಏರಿಳಿತ', false);
        $response->assertDontSee('ಬೆಲೆ ಏರಿಳಿತ (Volatility)', false);
        $response->assertSee('(ನಿವ್ವಳ ಲಾಭ ಹೋಲಿಕೆ)', false);
        $response->assertDontSee('(Net Profit Comparison)', false);
    }
}

