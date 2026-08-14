<?php

namespace Tests\Unit\ExternalReputation;

use App\Support\ExternalReputation\FakeOutscraperPlacesClient;
use PHPUnit\Framework\TestCase;

class FakeOutscraperPlacesClientTest extends TestCase
{
    public function test_fake_returns_metrics_per_query(): void
    {
        $client = new FakeOutscraperPlacesClient;
        $results = $client->fetchPlaces(['ChIJDemoPlace01', 'ChIJDemoPlace02']);

        $this->assertCount(2, $results);
        $this->assertNotNull($results['ChIJDemoPlace01']);
        $this->assertTrue($results['ChIJDemoPlace01']->hasStarsBreakdown());
        $this->assertGreaterThan(0, $results['ChIJDemoPlace01']->reviewsTotal);
        $this->assertSame(
            $results['ChIJDemoPlace01']->reviewsTotal,
            array_sum($results['ChIJDemoPlace01']->stars)
        );
    }
}
