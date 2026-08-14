<?php

namespace Tests\Unit\ExternalReputation;

use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;
use App\Support\ExternalReputation\NegativeReviewAlertDetector;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NegativeReviewAlertDetectorTest extends TestCase
{
    private NegativeReviewAlertDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new NegativeReviewAlertDetector;
    }

    #[Test]
    public function no_alert_without_previous_snapshot(): void
    {
        $current = $this->snapshot(['stars_1' => 2, 'stars_2' => 1, 'reviews_total' => 10]);

        $this->assertNull($this->detector->detect(null, $current));
    }

    #[Test]
    public function creates_negative_increase_when_one_star_rises(): void
    {
        $previous = $this->snapshot(['stars_1' => 1, 'stars_2' => 0, 'reviews_total' => 10]);
        $current = $this->snapshot(['stars_1' => 3, 'stars_2' => 0, 'reviews_total' => 12]);

        $result = $this->detector->detect($previous, $current);

        $this->assertSame(ClientExternalReputationAlert::KIND_NEGATIVE_INCREASE, $result['kind']);
        $this->assertSame(2, $result['delta_stars_1']);
        $this->assertSame(0, $result['delta_stars_2']);
        $this->assertSame(2, $result['delta_reviews_total']);
    }

    #[Test]
    public function creates_negative_increase_when_two_star_rises(): void
    {
        $previous = $this->snapshot(['stars_1' => 0, 'stars_2' => 1, 'reviews_total' => 10]);
        $current = $this->snapshot(['stars_1' => 0, 'stars_2' => 4, 'reviews_total' => 13]);

        $result = $this->detector->detect($previous, $current);

        $this->assertSame(ClientExternalReputationAlert::KIND_NEGATIVE_INCREASE, $result['kind']);
        $this->assertSame(0, $result['delta_stars_1']);
        $this->assertSame(3, $result['delta_stars_2']);
    }

    #[Test]
    public function prioritizes_negative_increase_even_if_total_also_drops(): void
    {
        $previous = $this->snapshot(['stars_1' => 1, 'stars_2' => 0, 'reviews_total' => 20]);
        $current = $this->snapshot(['stars_1' => 2, 'stars_2' => 0, 'reviews_total' => 18]);

        $result = $this->detector->detect($previous, $current);

        $this->assertSame(ClientExternalReputationAlert::KIND_NEGATIVE_INCREASE, $result['kind']);
        $this->assertSame(1, $result['delta_stars_1']);
        $this->assertSame(-2, $result['delta_reviews_total']);
    }

    #[Test]
    public function no_false_alert_when_only_total_would_be_ignored_without_drop_path(): void
    {
        // Total estable o sube, sin subida de 1/2 → nada
        $previous = $this->snapshot(['stars_1' => 1, 'stars_2' => 1, 'reviews_total' => 10]);
        $current = $this->snapshot(['stars_1' => 1, 'stars_2' => 1, 'reviews_total' => 12]);

        $this->assertNull($this->detector->detect($previous, $current));
    }

    #[Test]
    public function total_drop_without_one_two_star_increase_is_anomaly_not_negative(): void
    {
        $previous = $this->snapshot(['stars_1' => 2, 'stars_2' => 1, 'reviews_total' => 20]);
        $current = $this->snapshot(['stars_1' => 2, 'stars_2' => 1, 'reviews_total' => 17]);

        $result = $this->detector->detect($previous, $current);

        $this->assertSame(ClientExternalReputationAlert::KIND_TOTAL_DROP, $result['kind']);
        $this->assertSame(0, $result['delta_stars_1']);
        $this->assertSame(0, $result['delta_stars_2']);
        $this->assertSame(-3, $result['delta_reviews_total']);
    }

    #[Test]
    public function decreasing_one_star_count_does_not_alert_as_negative(): void
    {
        $previous = $this->snapshot(['stars_1' => 5, 'stars_2' => 2, 'reviews_total' => 20]);
        $current = $this->snapshot(['stars_1' => 3, 'stars_2' => 1, 'reviews_total' => 17]);

        $result = $this->detector->detect($previous, $current);

        $this->assertSame(ClientExternalReputationAlert::KIND_TOTAL_DROP, $result['kind']);
        $this->assertSame(0, $result['delta_stars_1']);
        $this->assertSame(0, $result['delta_stars_2']);
    }

    /**
     * @param  array{stars_1?: int, stars_2?: int, reviews_total?: int}  $attrs
     */
    private function snapshot(array $attrs): ClientExternalReputationSnapshot
    {
        $snap = new ClientExternalReputationSnapshot;
        $snap->forceFill([
            'stars_1' => $attrs['stars_1'] ?? 0,
            'stars_2' => $attrs['stars_2'] ?? 0,
            'stars_3' => 0,
            'stars_4' => 0,
            'stars_5' => 0,
            'reviews_total' => $attrs['reviews_total'] ?? 0,
        ]);

        return $snap;
    }
}
