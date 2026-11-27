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
 * Icons of the carriers and payment methods ($m4p_methods on the checkout and the order confirmation): one setting per
 * carrier (by its reference, which survives editing the carrier) and per payment module, with a default picked from
 * the module and the name, so a shop gets sensible icons without setting anything.
 */
class M4pCoreMethodIcons
{
    /** Icons of the theme sprite a carrier or a payment method can show. */
    public const ICONS = ['truck', 'truck-cold', 'warehouse', 'package', 'store', 'landmark', 'hand-coins', 'credit-card', 'building'];

    /** Lower-case fragments of a carrier name or module, and the icon they mean; the first match wins. */
    private const CARRIER_DEFAULTS = [
        'odbiór' => 'warehouse', 'odbior' => 'warehouse', 'pickup' => 'warehouse', 'collect' => 'warehouse', 'magazyn' => 'warehouse',
        'chłod' => 'truck-cold', 'chlod' => 'truck-cold', 'cold' => 'truck-cold', 'refriger' => 'truck-cold',
    ];
    private const PAYMENT_DEFAULTS = [
        'wirepayment' => 'landmark', 'bankwire' => 'landmark', 'transfer' => 'landmark', 'przelew' => 'landmark', 'checkpayment' => 'landmark',
        'cashondelivery' => 'hand-coins', 'cod' => 'hand-coins', 'pobranie' => 'hand-coins',
    ];

    public static function carrierKey(int $idReference): string
    {
        return 'CARRIER_ICON_' . $idReference;
    }

    public static function paymentKey(string $module): string
    {
        return 'PAYMENT_ICON_' . strtoupper((string) preg_replace('/[^A-Za-z0-9_]/', '', $module));
    }

    public static function paymentNameKey(string $module): string
    {
        return 'PAYMENT_NAME_' . strtoupper((string) preg_replace('/[^A-Za-z0-9_]/', '', $module));
    }

    public static function paymentNoteKey(string $module): string
    {
        return 'PAYMENT_NOTE_' . strtoupper((string) preg_replace('/[^A-Za-z0-9_]/', '', $module));
    }

    public static function carrierDefault(string $name, string $module = ''): string
    {
        return self::match(mb_strtolower($name . ' ' . $module), self::CARRIER_DEFAULTS, 'truck');
    }

    public static function paymentDefault(string $module): string
    {
        return self::match(strtolower($module), self::PAYMENT_DEFAULTS, 'credit-card');
    }

    public static function carrierIcon(Carrier $carrier): string
    {
        $icon = (string) M4pCoreSettings::get(self::carrierKey((int) $carrier->id_reference), '');

        return in_array($icon, self::ICONS, true) ? $icon : self::carrierDefault((string) $carrier->name, (string) $carrier->external_module_name);
    }

    public static function paymentIcon(string $module): string
    {
        $icon = (string) M4pCoreSettings::get(self::paymentKey($module), '');

        return in_array($icon, self::ICONS, true) ? $icon : self::paymentDefault($module);
    }

    /**
     * @return array{carriers: array<int, string>, payments: array<string, string>, payment_texts: array<string, array{name: string, note: string}>}
     *                                                                                  id_carrier => icon, module => icon, module => name shown instead of the
     *                                                                                  module's own ('' keeps it) and the line under it
     */
    public static function forFront(Context $context): array
    {
        $carriers = [];
        foreach (Carrier::getCarriers((int) $context->language->id, true, false, false, null, Carrier::ALL_CARRIERS) as $row) {
            $carriers[(int) $row['id_carrier']] = self::carrierIcon(new Carrier((int) $row['id_carrier']));
        }
        $payments = [];
        $texts = [];
        foreach (self::paymentModules() as $module) {
            $payments[$module] = self::paymentIcon($module);
            $texts[$module] = [
                'name' => (string) M4pCoreSettings::get(self::paymentNameKey($module), ''),
                'note' => (string) M4pCoreSettings::get(self::paymentNoteKey($module), ''),
            ];
        }

        return ['carriers' => $carriers, 'payments' => $payments, 'payment_texts' => $texts];
    }

    /**
     * @return string[] names of the installed payment modules
     */
    public static function paymentModules(): array
    {
        return array_values(array_map(static fn (array $row): string => (string) $row['name'], PaymentModule::getInstalledPaymentModules()));
    }

    /**
     * @param array<string, string> $map
     */
    private static function match(string $haystack, array $map, string $fallback): string
    {
        foreach ($map as $fragment => $icon) {
            if (strpos($haystack, $fragment) !== false) {
                return $icon;
            }
        }

        return $fallback;
    }
}
