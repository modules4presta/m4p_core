<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * The sales department: its phone and weekly hours, one source for the header strip, the footer and
 * m4p_contactwidget. Empty fields fall back to m4p_contactwidget and the shop phone.
 */
class M4pCoreSales
{
    public const PHONE = 'SALES_PHONE';
    public const HOURS = 'SALES_HOURS';
    public const EMAIL = 'SALES_EMAIL';

    private const WIDGET_PHONE = 'M4P_CONTACTWIDGET_PHONE';
    private const WIDGET_SCHEDULE = 'M4P_CONTACTWIDGET_SCHEDULE';

    public static function phone(): string
    {
        $phone = trim((string) M4pCoreSettings::get(self::PHONE, ''));
        if ($phone === '' && Module::isEnabled('m4p_contactwidget')) {
            $phone = trim((string) Configuration::get(self::WIDGET_PHONE));
        }

        return $phone !== '' ? $phone : trim((string) Configuration::get('PS_SHOP_PHONE'));
    }

    public static function email(): string
    {
        $email = trim((string) M4pCoreSettings::get(self::EMAIL, ''));

        return Validate::isEmail($email) ? $email : trim((string) Configuration::get('PS_SHOP_EMAIL'));
    }

    /**
     * The week as stored: {"1":["07:30","16:00"],…}, ISO day numbers, days without a range are closed.
     */
    public static function weekJson(): string
    {
        $week = trim((string) M4pCoreSettings::get(self::HOURS, ''));
        if ($week === '' && Module::isEnabled('m4p_contactwidget')) {
            $week = trim((string) Configuration::get(self::WIDGET_SCHEDULE));
        }

        return $week;
    }

    /**
     * @return array<int, array{0: string, 1: string}> day => [from, to] for the open days
     */
    public static function week(?string $json = null): array
    {
        $data = json_decode($json ?? self::weekJson(), true);
        $week = [];
        foreach (is_array($data) ? $data : [] as $day => $range) {
            $day = (int) $day;
            if ($day >= 1 && $day <= 7 && is_array($range) && count($range) === 2
                && self::isTime((string) $range[0]) && self::isTime((string) $range[1]) && $range[0] < $range[1]) {
                $week[$day] = [(string) $range[0], (string) $range[1]];
            }
        }
        ksort($week);

        return $week;
    }

    /**
     * Neighbouring days with the same hours joined: ["pn–pt 7:30–16:00", "sob 7:00–12:00"].
     *
     * @param array<int, string> $dayNames ISO day number => short name
     *
     * @return string[]
     */
    public static function lines(array $dayNames): array
    {
        $groups = [];
        foreach (self::week() as $day => $range) {
            $last = end($groups);
            if ($last && $last['range'] === $range && $last['to'] === $day - 1) {
                $groups[key($groups)]['to'] = $day;
                continue;
            }
            $groups[] = ['from' => $day, 'to' => $day, 'range' => $range];
        }
        $lines = [];
        foreach ($groups as $group) {
            $days = $group['from'] === $group['to']
                ? $dayNames[$group['from']]
                : $dayNames[$group['from']] . '–' . $dayNames[$group['to']];
            $lines[] = $days . ' ' . self::shortTime($group['range'][0]) . '–' . self::shortTime($group['range'][1]);
        }

        return $lines;
    }

    /**
     * Form input ("7:30-16:00" per day, empty = closed) to the stored week; null when a day cannot be read.
     *
     * @param array<int|string, mixed> $days
     */
    public static function fromInput(array $days): ?string
    {
        $week = [];
        foreach ($days as $day => $text) {
            $day = (int) $day;
            $text = trim((string) $text);
            if ($day < 1 || $day > 7 || $text === '') {
                continue;
            }
            if (!preg_match('/^(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})$/u', $text, $m)) {
                return null;
            }
            $from = sprintf('%02d:%s', (int) $m[1], $m[2]);
            $to = sprintf('%02d:%s', (int) $m[3], $m[4]);
            if (!self::isTime($from) || !self::isTime($to) || $from >= $to) {
                return null;
            }
            $week[(string) $day] = [$from, $to];
        }

        return $week ? (string) json_encode($week) : '';
    }

    /**
     * @return array<int, string> day => "7:30-16:00" for the form
     */
    public static function toInput(string $json): array
    {
        $input = [];
        foreach (self::week($json) as $day => $range) {
            $input[$day] = self::shortTime($range[0]) . '-' . self::shortTime($range[1]);
        }

        return $input;
    }

    private static function isTime(string $value): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value);
    }

    private static function shortTime(string $value): string
    {
        [$hours, $minutes] = explode(':', $value);

        return (int) $hours . ':' . $minutes;
    }
}
