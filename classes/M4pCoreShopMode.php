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
 * Whether the current visitor gets the company (B2B) or the consumer (B2C) view.
 * Another module can replace the configured model for one visitor on the
 * `actionM4pCoreShopMode` hook, for example for a B2B/B2C switch on a demo shop.
 */
class M4pCoreShopMode
{
    public const B2B = 'b2b';
    public const B2C = 'b2c';
    public const MIXED = 'mixed';

    /** @var array<string, string> */
    private static $resolved = [];

    public static function configured(): string
    {
        $mode = (string) M4pCoreSettings::get('SHOP_MODE', self::B2C);

        return in_array($mode, [self::B2B, self::B2C, self::MIXED], true) ? $mode : self::B2C;
    }

    /**
     * @return string self::B2B or self::B2C
     */
    public static function current(?Context $context = null): string
    {
        $context = $context ?: Context::getContext();
        $customer = $context->customer;
        $idCustomer = Validate::isLoadedObject($customer) && $customer->isLogged() ? (int) $customer->id : 0;

        if (isset(self::$resolved[$idCustomer])) {
            return self::$resolved[$idCustomer];
        }

        $mode = self::configured();
        Hook::exec('actionM4pCoreShopMode', ['mode' => &$mode, 'configured' => self::configured()]);

        if ($mode === self::MIXED) {
            $mode = $idCustomer && self::inCompanyGroup($idCustomer) ? self::B2B : self::B2C;
        }

        return self::$resolved[$idCustomer] = $mode === self::B2C ? self::B2C : self::B2B;
    }

    public static function isB2c(?Context $context = null): bool
    {
        return self::current($context) === self::B2C;
    }

    /** Consumers see prices without logging in; companies after logging in. */
    public static function showsPrices(?Context $context = null): bool
    {
        $context = $context ?: Context::getContext();

        return self::isB2c($context) || (Validate::isLoadedObject($context->customer) && $context->customer->isLogged());
    }

    /**
     * @return int[] every customer group except the visitor and guest groups
     */
    public static function defaultCompanyGroups(): array
    {
        $skip = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP'), (int) Configuration::get('PS_GUEST_GROUP')];
        $ids = array_map('intval', array_column(Group::getGroups((int) Configuration::get('PS_LANG_DEFAULT')), 'id_group'));

        return array_values(array_diff($ids, $skip));
    }

    private static function inCompanyGroup(int $idCustomer): bool
    {
        $setting = (string) M4pCoreSettings::get('B2B_GROUPS', '');
        $companyGroups = $setting === '' ? self::defaultCompanyGroups() : array_map('intval', explode(',', $setting));

        return (bool) array_intersect($companyGroups, array_map('intval', Customer::getGroupsStatic($idCustomer)));
    }
}
