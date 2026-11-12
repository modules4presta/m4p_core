<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Reads and writes the settings the Modules4Presta modules share. Every value is
 * one row in `configuration` under the `M4P_CORE_` prefix, stored for the shop
 * being edited, so a multistore keeps one set per shop.
 */
class M4pCoreSettings
{
    public const PREFIX = 'M4P_CORE_';

    /** Raised when the meaning of a stored key changes; children compare against it. */
    public const SCHEMA = 1;

    public const SCHEMA_KEY = 'M4P_CORE_SCHEMA';

    /**
     * @param string $key short name, such as COMPANY_PHONE
     * @param mixed $default
     * @param int|null $idShop
     *
     * @return mixed
     */
    public static function get(string $key, $default = null, ?int $idShop = null)
    {
        $value = Configuration::get(self::PREFIX . $key, null, null, $idShop);

        return $value === false || $value === null || $value === '' ? $default : $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $idShop the shop being written to; the current one when left out
     *
     * @return bool
     */
    public static function set(string $key, $value, ?int $idShop = null): bool
    {
        if ($idShop === null && Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        return Configuration::updateValue(self::PREFIX . $key, $value, false, null, $idShop);
    }

    /**
     * Name of the shop these settings belong to: the one the multistore selector shows
     * while it is in use, the shop name from the front office otherwise.
     *
     * @return string
     */
    public static function shopName(): string
    {
        $shop = Context::getContext()->shop;

        if (Shop::isFeatureActive()) {
            return (string) $shop->name;
        }

        $name = (string) Configuration::get('PS_SHOP_NAME');

        return $name !== '' ? $name : (string) $shop->name;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Every value of one section, keyed by its short name.
     *
     * @param array<string, mixed> $section
     *
     * @return array<string, mixed>
     */
    public static function ofSection(array $section): array
    {
        $out = [];
        foreach ($section['fields'] ?? [] as $field) {
            $out[$field['key']] = self::get($field['key'], $field['default'] ?? '');
        }

        return $out;
    }

    /**
     * Writes the fields of one section, dropping whatever does not fit the field
     * type. Reports both the keys that were refused and the ones stored with a
     * different value than was sent: PrestaShop takes tags out of a
     * configuration value on the way in, and long text is cut to the column.
     *
     * @param array<string, mixed> $section
     * @param array<string, mixed> $input
     *
     * @return array{refused: string[], changed: string[]}
     */
    public static function saveSection(array $section, array $input): array
    {
        $refused = [];
        $changed = [];

        foreach ($section['fields'] ?? [] as $field) {
            $key = (string) $field['key'];
            $type = (string) ($field['type'] ?? 'text');
            $raw = $input[$key] ?? null;

            if ($type === 'switch') {
                self::set($key, empty($raw) ? 0 : 1);
                continue;
            }

            if ($type === 'file') {
                continue;
            }

            $value = is_scalar($raw) ? trim((string) $raw) : '';

            if ($value === '') {
                self::set($key, '');
                continue;
            }

            $clean = self::clean($type, $value, $field);
            if ($clean === null) {
                $refused[] = $key;
                continue;
            }

            self::set($key, $clean);

            if ((string) self::get($key, '') !== $value) {
                $changed[] = $key;
            }
        }

        return ['refused' => $refused, 'changed' => $changed];
    }

    /**
     * @param string $type
     * @param string $value
     * @param array<string, mixed> $field
     *
     * @return string|null null when the value does not fit the type
     */
    private static function clean(string $type, string $value, array $field): ?string
    {
        switch ($type) {
            case 'email':
                return Validate::isEmail($value) ? $value : null;
            case 'url':
                return preg_match('~^https?://~i', $value) ? $value : null;
            case 'tel':
                return preg_match('~^[+0-9 ()./-]{6,30}$~', $value) ? $value : null;
            case 'number':
                if (!preg_match('/^-?[0-9]+$/', $value)) {
                    return null;
                }
                $number = (int) $value;
                $min = isset($field['min']) ? (int) $field['min'] : null;
                $max = isset($field['max']) ? (int) $field['max'] : null;

                if (($min !== null && $number < $min) || ($max !== null && $number > $max)) {
                    return null;
                }

                return (string) $number;
            case 'color':
                return M4pCoreTheme::isColor($value) ? strtolower($value) : null;
            case 'css':
                return self::isSafeCss($value) ? $value : null;
            case 'select':
                $allowed = array_keys($field['options'] ?? []);

                return in_array($value, array_map('strval', $allowed), true) ? $value : null;
            case 'textarea':
                return Validate::isCleanHtml($value) ? Tools::substr($value, 0, 1000) : null;
            default:
                return Validate::isCleanHtml($value) ? Tools::substr($value, 0, 255) : null;
        }
    }

    /**
     * A value that can stand after `--token:` and cannot end the declaration,
     * open a block or load anything.
     *
     * @param string $value
     *
     * @return bool
     */
    private static function isSafeCss(string $value): bool
    {
        return strlen($value) <= 200
            && (bool) preg_match('~^[a-z0-9#%(),./\s\'"+*-]+$~i', $value)
            && !preg_match('~url\s*\(|expression|@import|/\*~i', $value);
    }
}
