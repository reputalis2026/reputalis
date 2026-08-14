<?php

namespace Tests\Unit\ExternalReputation;

use App\Support\ExternalReputation\PlaceMetrics;
use App\Support\ExternalReputation\RatingProjection;
use PHPUnit\Framework\TestCase;

class RatingProjectionTest extends TestCase
{
    public function test_calculated_rating_from_stars(): void
    {
        $stars = [1 => 2, 2 => 1, 3 => 3, 4 => 10, 5 => 80];
        // (2+2+9+40+400)/96 = 453/96 = 4.71875 → 4.7188
        $this->assertSame(4.7188, RatingProjection::calculatedRating($stars));
    }

    public function test_calculated_rating_empty_returns_null(): void
    {
        $this->assertNull(RatingProjection::calculatedRating([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]));
    }

    public function test_five_stars_needed_when_already_above_target(): void
    {
        $stars = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 100];
        $this->assertSame(0, RatingProjection::fiveStarsNeededForTarget($stars, 4.5));
    }

    public function test_five_stars_needed_for_target(): void
    {
        $stars = [1 => 10, 2 => 10, 3 => 10, 4 => 10, 5 => 10];
        // media 3.0; para 4.5: n >= (4.5*50 - 150)/(5-4.5) = (225-150)/0.5 = 150
        $this->assertSame(150, RatingProjection::fiveStarsNeededForTarget($stars, 4.5));
    }

    public function test_parse_stars_from_reviews_per_score_object(): void
    {
        $stars = PlaceMetrics::parseStars([
            'reviews_per_score' => [
                '1' => 4,
                '2' => 2,
                '3' => 4,
                '4' => 19,
                '5' => 323,
            ],
        ]);

        $this->assertSame([1 => 4, 2 => 2, 3 => 4, 4 => 19, 5 => 323], $stars);
    }

    public function test_parse_stars_from_column_fields(): void
    {
        $stars = PlaceMetrics::parseStars([
            'reviews_per_score_1' => 1,
            'reviews_per_score_2' => 2,
            'reviews_per_score_3' => 3,
            'reviews_per_score_4' => 4,
            'reviews_per_score_5' => 5,
        ]);

        $this->assertSame([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], $stars);
    }

    public function test_from_outscraper_place_builds_metrics(): void
    {
        $metrics = PlaceMetrics::fromOutscraperPlace('ChIJtest', [
            'name' => 'Demo',
            'place_id' => 'ChIJtest',
            'google_id' => '0x1:0x2',
            'rating' => 4.7,
            'reviews' => 3807,
            'reviews_per_score' => [
                '1' => 102,
                '2' => 64,
                '3' => 123,
                '4' => 379,
                '5' => 3139,
            ],
            'email_1' => 'should-not-keep@example.com',
        ]);

        $this->assertSame('ChIJtest', $metrics->placeId);
        $this->assertSame(4.7, $metrics->rating);
        $this->assertSame(3807, $metrics->reviewsTotal);
        $this->assertSame(3139, $metrics->stars[5]);
        $this->assertArrayNotHasKey('email_1', $metrics->raw ?? []);
        $this->assertTrue($metrics->hasStarsBreakdown());
    }
}
