<?php

namespace App\Support;

final class WbsDateHelper
{
    /**
     * The month labels used by WBS periods.
     *
     * @var array<int, string>
     */
    public const MONTH_LABELS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];

    /**
     * Map a date to W1-W4 using the Procurement convention already used in the frontend.
     *
     * Rules:
     * - W1 = day 1-10
     * - W2 = day 11-17
     * - W3 = day 18-24
     * - W4 = day 25-31
     *
     * @return array{year: int, month: int, week: int}
     */
    public static function resolveWeekInfo(\DateTimeInterface|string $date, string $timezone = 'Asia/Jakarta'): array
    {
        $dateTime = $date instanceof \DateTimeInterface
            ? new \DateTimeImmutable($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone))
            : new \DateTimeImmutable($date, new \DateTimeZone($timezone));

        $dateTime = $dateTime->setTimezone(new \DateTimeZone($timezone));

        return [
            'year' => (int) $dateTime->format('Y'),
            'month' => (int) $dateTime->format('n'),
            'week' => self::resolveWeekFromDay((int) $dateTime->format('j')),
        ];
    }

    public static function resolveWeekFromDay(int $day): int
    {
        if ($day >= 1 && $day <= 10) {
            return 1;
        }

        if ($day >= 11 && $day <= 17) {
            return 2;
        }

        if ($day >= 18 && $day <= 24) {
            return 3;
        }

        return 4;
    }

    public static function periodOrdinal(int $year, int $month, int $week): int
    {
        return ($year * 48) + (($month - 1) * 4) + $week;
    }

    public static function periodLabel(int $month, int $week): string
    {
        return self::MONTH_LABELS[$month].' W'.$week;
    }

    public static function periodOrdinalFromLabel(string $label, int $year): ?int
    {
        $trimmed = trim($label);

        if (! preg_match('/^([A-Za-z]{3})\s+W(\d+)$/', $trimmed, $matches)) {
            return null;
        }

        $month = array_search($matches[1], self::MONTH_LABELS, true);

        if ($month === false) {
            return null;
        }

        $week = (int) $matches[2];

        if ($week < 1 || $week > 4) {
            return null;
        }

        return self::periodOrdinal($year, $month, $week);
    }
}
