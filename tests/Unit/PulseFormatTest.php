<?php

namespace Tests\Unit;

use App\Support\Pulse\PulseFormat;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PulseFormatTest extends TestCase
{
    public function test_spanish_numbers_and_ordinals(): void
    {
        $this->assertSame('4,63', PulseFormat::decimal(4.634, 2));
        $this->assertSame('1.284', PulseFormat::integer(1284));
        $this->assertSame('93,1 %', PulseFormat::percent(93.14, 1));
        $this->assertSame('4.º', PulseFormat::ordinal(4));
        $this->assertSame('3.ª', PulseFormat::ordinal(3, true));
        $this->assertNull(PulseFormat::decimal(null, 1));
    }

    public function test_dates_use_spanish_month_names(): void
    {
        $date = Carbon::parse('2026-09-07');

        $this->assertSame('7 sep 2026', PulseFormat::dayMonth($date));
        $this->assertSame('Sep', PulseFormat::monthShort($date));
    }
}
