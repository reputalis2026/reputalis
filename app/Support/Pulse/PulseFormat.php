<?php

namespace App\Support\Pulse;

use Carbon\CarbonInterface;

final class PulseFormat
{
    public static function decimal(?float $value, int $decimals): ?string
    {
        if ($value === null) {
            return null;
        }

        return number_format($value, $decimals, ',', '.');
    }

    public static function integer(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public static function percent(?float $value, int $decimals = 1): ?string
    {
        $formatted = self::decimal($value, $decimals);

        return $formatted === null ? null : $formatted.' %';
    }

    public static function ordinal(int $value, bool $feminine = false): string
    {
        return $value.'.'.($feminine ? 'ª' : 'º');
    }

    public static function dayMonth(CarbonInterface $date): string
    {
        $months = [
            1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
            5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
            9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
        ];

        return $date->day.' '.$months[(int) $date->month].' '.$date->year;
    }

    public static function monthShort(CarbonInterface $date): string
    {
        $months = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        return $months[(int) $date->month];
    }
}
