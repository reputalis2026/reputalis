<?php

namespace Tests\Unit;

use App\Support\Pulse\FakePulseSector;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FakePulseSectorTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_postal_ranking_matches_the_app_layout(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 12:00:00', 'Europe/Madrid'));

        $sector = (new FakePulseSector)->build('client-1', '04740', 'Almería');

        $this->assertSame('04740', $sector['postal_code']);
        $this->assertSame('Almería', $sector['province']);
        $this->assertSame(28, $sector['analyzed']);
        $this->assertSame(4, $sector['position']);
        $this->assertSame(3, $sector['ahead']);
        $this->assertSame(24, $sector['behind']);
        $this->assertSame(3, $sector['projection_position']);
        $this->assertCount(5, $sector['nearby']);
        $this->assertTrue($sector['nearby'][2]['is_you']);
        $this->assertSame('Tu farmacia', $sector['nearby'][2]['name']);
        $this->assertCount(6, $sector['evolution']);
        $this->assertSame(4, $sector['evolution'][5]['position']);
        $this->assertSame('May', $sector['evolution'][0]['label']);
        $this->assertSame('Oct', $sector['evolution'][5]['label']);
    }

    public function test_missing_postal_code_is_not_invented(): void
    {
        $sector = (new FakePulseSector)->build('client-2', '', '');

        $this->assertSame('', $sector['postal_code']);
        $this->assertNull($sector['province']);
        $this->assertSame('—', $sector['territories'][0]['name']);
        $this->assertSame('—', $sector['territories'][1]['name']);
    }
}
