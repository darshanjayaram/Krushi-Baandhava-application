<?php

namespace Tests\Unit;

use App\Services\Forecast\Models\HoltsLinearTrendModel;
use Tests\TestCase;

class HoltsLinearTrendModelTest extends TestCase
{
    public function test_holts_linear_predicts_upward_trajectory(): void
    {
        $model = new HoltsLinearTrendModel(alpha: 0.4, beta: 0.2);

        // Steady upward sequence: 1000, 1100, 1200, 1300, 1400...
        $prices = [];
        for ($i = 0; $i < 30; $i++) {
            $prices[] = 1000.0 + ($i * 100.0);
        }

        $forecast7d = $model->forecast($prices, 7);

        // Last price was 3900. With upward slope ~100/day, 7d expectation should be higher than 3900
        $this->assertGreaterThan(3900.0, $forecast7d['expected_price']);
        $this->assertTrue($forecast7d['expected_price'] <= $forecast7d['upper_bound']);
        $this->assertTrue($forecast7d['lower_bound'] <= $forecast7d['expected_price']);
        $this->assertTrue($forecast7d['lower_bound'] >= 0.0);
        $this->assertGreaterThan(50.0, $forecast7d['confidence_score']);
    }

    public function test_bounds_widen_with_longer_horizons(): void
    {
        $model = new HoltsLinearTrendModel();

        // Historical series with minor variance
        $prices = [45000, 45200, 44900, 45300, 45100, 45400, 45250, 45600, 45500, 45800];

        $proj1d = $model->forecast($prices, 1);
        $proj30d = $model->forecast($prices, 30);

        $spread1d = $proj1d['upper_bound'] - $proj1d['lower_bound'];
        $spread30d = $proj30d['upper_bound'] - $proj30d['lower_bound'];

        // Uncertainty must expand for 30-day forecast compared to 1-day
        $this->assertGreaterThan($spread1d, $spread30d);
        // Confidence must decrease for 30-day compared to 1-day
        $this->assertLessThan($proj1d['confidence_score'], $proj30d['confidence_score']);
    }

    public function test_handles_degenerate_or_short_series(): void
    {
        $model = new HoltsLinearTrendModel();

        $empty = $model->forecast([], 7);
        $this->assertEquals(0.0, $empty['expected_price']);

        $single = $model->forecast([50000.0], 7);
        $this->assertEquals(50000.0, $single['expected_price']);
        $this->assertEquals(50000.0, $single['lower_bound']);
    }
}
