<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Regression coverage for the "How to Use" onboarding tour
 * (resources/views/components/onboarding-tour.blade.php).
 */
class OnboardingTourTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_page_renders_tour_component_and_engine(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('id="kbTourBubble"', false);
        $response->assertSee('id="kbTourHole"', false);
        $response->assertSee('window.startKrushiTour', false);
        $response->assertSee('krushi_tour_done', false);
    }

    public function test_home_page_contains_all_tour_anchor_targets(): void
    {
        $html = $this->get(route('home'))->assertStatus(200)->getContent();

        // Always present on home
        $this->assertStringContainsString('id="tourHeroLocationCard"', $html);
        $this->assertStringContainsString('id="tourPricesSection"', $html);

        // Anchor IDs must render as real attributes (not HTML-escaped)
        $this->assertStringNotContainsString('id=&quot;tourCropCard&quot;', $html);
    }

    public function test_how_to_use_button_starts_tour_instead_of_linking_to_articles(): void
    {
        $html = $this->get(route('home'))->assertStatus(200)->getContent();

        $this->assertStringContainsString('id="tourStartButton"', $html);
        $this->assertMatchesRegularExpression(
            '/id="tourStartButton"\s+onclick="window\.startKrushiTour/',
            $html
        );
    }

    public function test_tour_has_bilingual_step_content_and_audio_fallback(): void
    {
        $html = $this->get(route('home'))->assertStatus(200)->getContent();

        // Kannada + English text for step 1
        $this->assertStringContainsString('ನಿಮ್ಮ ಊರು ಅಥವಾ ಜಿಲ್ಲೆ', $html);
        $this->assertStringContainsString('set your village or district', $html);

        // MP3 base path + Web Speech fallback
        $this->assertStringContainsString('audio\/tour', $html);
        $this->assertStringContainsString('SpeechSynthesisUtterance', $html);
    }

    public function test_tour_renders_in_both_locales(): void
    {
        foreach (['kn', 'en'] as $locale) {
            $this->get(route('locale.switch', $locale));
            $this->get(route('home'))
                ->assertStatus(200)
                ->assertSee('id="kbTourBubble"', false);
        }
    }
}
