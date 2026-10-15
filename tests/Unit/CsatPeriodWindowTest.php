<?php

namespace Tests\Unit;

use App\Support\ClientDashboard\InternalReputationDateRange;
use App\Support\CsatMetrics;
use Carbon\Carbon;
use Tests\TestCase;

class CsatPeriodWindowTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_week_and_month_include_today_in_madrid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 23:30:00', 'Europe/Madrid'));
        config([
            'app.timezone' => 'UTC',
            'elpulso.timezone' => 'Europe/Madrid',
        ]);

        [$weekFrom, $weekTo] = CsatMetrics::dateRangeForPeriod(CsatMetrics::PERIOD_7_DAYS);
        [$monthFrom, $monthTo] = CsatMetrics::dateRangeForPeriod(CsatMetrics::PERIOD_30_DAYS);
        [$todayFrom, $todayTo] = CsatMetrics::dateRangeForPeriod(CsatMetrics::PERIOD_TODAY);

        $this->assertSame('2026-10-07 22:00:00', $weekFrom);
        $this->assertSame('2026-10-14 21:59:59', $weekTo);
        $this->assertSame('2026-09-14 22:00:00', $monthFrom);
        $this->assertSame('2026-10-14 21:59:59', $monthTo);
        $this->assertSame('2026-10-13 22:00:00', $todayFrom);
        $this->assertSame('2026-10-14 21:59:59', $todayTo);

        $dashboardWeek = InternalReputationDateRange::fromState(
            InternalReputationDateRange::TYPE_LAST_WEEK,
            null,
            null,
        )->bounds();

        $this->assertSame($weekFrom, $dashboardWeek[0]->toDateTimeString());
        $this->assertSame($weekTo, $dashboardWeek[1]->toDateTimeString());
    }
}
