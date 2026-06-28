<?php

namespace App\Support\ClientDashboard;

use App\Support\CsatMetrics;
use Carbon\Carbon;

class InternalReputationDateRange
{
    public const TYPE_ALL = 'all';

    public const TYPE_LAST_MONTH = 'last_month';

    public const TYPE_LAST_WEEK = 'last_week';

    public const TYPE_TODAY = 'today';

    public const TYPE_CUSTOM = 'custom';

    public function __construct(
        public readonly string $rangeType = self::TYPE_ALL,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
    ) {}

    public static function fromState(?string $rangeType, ?string $dateFrom, ?string $dateTo): self
    {
        $rangeType = self::normalizeRangeType($rangeType);

        return new self(
            $rangeType,
            filled($dateFrom) ? (string) $dateFrom : null,
            filled($dateTo) ? (string) $dateTo : null,
        );
    }

    /**
     * @return array<int, string>
     */
    public static function rangeTypes(): array
    {
        return [
            self::TYPE_ALL,
            self::TYPE_LAST_MONTH,
            self::TYPE_LAST_WEEK,
            self::TYPE_TODAY,
            self::TYPE_CUSTOM,
        ];
    }

    public static function normalizeRangeType(?string $rangeType): string
    {
        $rangeType = (string) $rangeType;

        return in_array($rangeType, self::rangeTypes(), true)
            ? $rangeType
            : self::TYPE_ALL;
    }

    public function isCustom(): bool
    {
        return $this->rangeType === self::TYPE_CUSTOM;
    }

    public function isAll(): bool
    {
        return $this->rangeType === self::TYPE_ALL;
    }

    public function csatPeriod(): ?string
    {
        return match ($this->rangeType) {
            self::TYPE_ALL => CsatMetrics::PERIOD_ALL,
            self::TYPE_LAST_MONTH => CsatMetrics::PERIOD_30_DAYS,
            self::TYPE_LAST_WEEK => CsatMetrics::PERIOD_7_DAYS,
            self::TYPE_TODAY => CsatMetrics::PERIOD_TODAY,
            default => null,
        };
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function bounds(): array
    {
        if ($this->isCustom()) {
            $from = $this->parseDate($this->dateFrom)?->startOfDay();
            $to = $this->parseDate($this->dateTo)?->endOfDay();

            if ($from && $to && $from->greaterThan($to)) {
                return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to];
        }

        return match ($this->rangeType) {
            self::TYPE_LAST_MONTH => [Carbon::today()->subDays(30)->startOfDay(), Carbon::today()->endOfDay()],
            self::TYPE_LAST_WEEK => [Carbon::today()->subDays(7)->startOfDay(), Carbon::today()->endOfDay()],
            self::TYPE_TODAY => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            default => [null, null],
        };
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! filled($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
