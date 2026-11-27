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
 * Product page data of the M4P theme ($m4p_product): origin, sale unit and the facts next to the price, read from the
 * product features chosen in the Product page section, and the estimate per pack of a product sold by weight.
 */
class M4pCoreProductView
{
    public const ORIGIN = 'PDP_ORIGIN_FEATURE';
    public const UNIT = 'PDP_UNIT_FEATURE';
    public const GUEST_TEXT = 'PDP_GUEST_TEXT';
    public const FACTS = 3;
    public const INGREDIENTS = 'PDP_INGREDIENTS_FEATURE';
    public const ALLERGENS = 'PDP_ALLERGENS_FEATURE';
    public const FREE_FROM = 'PDP_FREE_FROM_FEATURE';
    public const NUTRITION_BASIS = 'PDP_NUTRITION_BASIS_FEATURE';
    public const NUTRITION = 'PDP_NUTRITION_FEATURES';
    public const NUTRITION_SUB = 'PDP_NUTRITION_SUB_FEATURES';

    /** Icons of the theme sprite a fact can show. */
    public const ICONS = ['package', 'snowflake', 'thermometer', 'calendar', 'clock', 'scale', 'boxes', 'truck', 'map-pin', 'tag', 'award', 'shield-check', 'info'];

    /** Sale units that mean the price is per kilogram and the pack is weighed. */
    private const WEIGHT_UNITS = ['kg'];

    public static function factKey(int $slot): string
    {
        return 'PDP_FACT_' . $slot . '_FEATURE';
    }

    public static function iconKey(int $slot): string
    {
        return 'PDP_FACT_' . $slot . '_ICON';
    }

    /**
     * shown: the features the page already prints (top of the page, composition, nutrition), left out of the
     * remaining feature list.
     *
     * @return array{origin: string, unit: string, weighed: bool, estimate: string, facts: array<int, array{icon: string, label: string, value: string}>, composition: array{ingredients: array{label: string, value: string}, allergens: array{label: string, value: string, items: string[]}, free_from: array{label: string, value: string}}, nutrition: array{basis: string, rows: array<int, array{label: string, value: string, sub: bool}>}, shown: int[], guest_text: string}
     */
    public static function of(Context $context, int $idProduct): array
    {
        $values = [];
        $names = [];
        foreach (Product::getFrontFeaturesStatic((int) $context->language->id, $idProduct) as $feature) {
            $id = (int) $feature['id_feature'];
            $values[$id] = isset($values[$id]) ? $values[$id] . ', ' . $feature['value'] : (string) $feature['value'];
            $names[$id] = (string) $feature['name'];
        }

        $origin = (int) M4pCoreSettings::get(self::ORIGIN, 0);
        $unitId = (int) M4pCoreSettings::get(self::UNIT, 0);
        $shown = array_values(array_filter([$origin, $unitId], static fn (int $id): bool => isset($values[$id])));

        $facts = [];
        for ($slot = 1; $slot <= self::FACTS; ++$slot) {
            $id = (int) M4pCoreSettings::get(self::factKey($slot), 0);
            if (!isset($values[$id])) {
                continue;
            }
            $icon = (string) M4pCoreSettings::get(self::iconKey($slot), 'info');
            $facts[] = ['icon' => in_array($icon, self::ICONS, true) ? $icon : 'info', 'label' => $names[$id], 'value' => $values[$id]];
            $shown[] = $id;
        }

        $allergens = self::valueOf($values, $names, $shown, self::ALLERGENS);
        $allergens['items'] = self::listOf($allergens['value']);
        $composition = [
            'ingredients' => self::valueOf($values, $names, $shown, self::INGREDIENTS),
            'allergens' => $allergens,
            'free_from' => self::valueOf($values, $names, $shown, self::FREE_FROM),
        ];
        $nutrition = self::nutrition($values, $names, $shown);

        $unit = $values[$unitId] ?? '';
        $weighed = in_array(mb_strtolower(trim($unit)), self::WEIGHT_UNITS, true);

        return [
            'origin' => $values[$origin] ?? '',
            'unit' => $unit,
            'weighed' => $weighed,
            'estimate' => $weighed ? self::estimate($context, $idProduct) : '',
            'facts' => $facts,
            'composition' => $composition,
            'nutrition' => $nutrition,
            'shown' => array_values(array_unique($shown)),
            'guest_text' => trim((string) M4pCoreSettings::get(self::GUEST_TEXT, '')),
        ];
    }

    /**
     * The name and value of the feature chosen in a setting, marked as shown; an empty value without it.
     *
     * @param array<int, string> $values
     * @param array<int, string> $names
     * @param int[] $shown
     *
     * @return array{label: string, value: string}
     */
    private static function valueOf(array $values, array $names, array &$shown, string $setting): array
    {
        $id = (int) M4pCoreSettings::get($setting, 0);
        if (!isset($values[$id]) || trim($values[$id]) === '') {
            return ['label' => '', 'value' => ''];
        }
        $shown[] = $id;

        return ['label' => $names[$id], 'value' => trim($values[$id])];
    }

    /**
     * @return string[] the items of a list typed with commas or semicolons
     */
    private static function listOf(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;]/', $value) ?: [])));
    }

    /**
     * Rows of the nutrition table in the order of the features; the rows chosen as "of which" are indented.
     *
     * @param array<int, string> $values
     * @param array<int, string> $names
     * @param int[] $shown
     *
     * @return array{basis: string, rows: array<int, array{label: string, value: string, sub: bool}>}
     */
    private static function nutrition(array $values, array $names, array &$shown): array
    {
        $picked = array_map('intval', explode(',', (string) M4pCoreSettings::get(self::NUTRITION, '')));
        $sub = array_map('intval', explode(',', (string) M4pCoreSettings::get(self::NUTRITION_SUB, '')));
        $rows = [];
        foreach ($values as $id => $value) {
            if (!in_array($id, $picked, true)) {
                continue;
            }
            $rows[] = ['label' => $names[$id], 'value' => $value, 'sub' => in_array($id, $sub, true)];
            $shown[] = $id;
        }
        $basis = self::valueOf($values, $names, $shown, self::NUTRITION_BASIS);

        return ['basis' => $rows ? $basis['value'] : '', 'rows' => $rows];
    }

    /**
     * Price of one pack of a product sold per kilogram: the price per kg times the product weight, in the price
     * display of the customer; empty without a weight or for a 1 kg pack, where it repeats the price.
     */
    private static function estimate(Context $context, int $idProduct): string
    {
        $weight = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `weight` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . $idProduct
        );
        if ($weight <= 0 || abs($weight - 1.0) < 0.001) {
            return '';
        }
        $withTax = Product::getTaxCalculationMethod((int) $context->customer->id) !== PS_TAX_EXC;
        $price = Product::getPriceStatic($idProduct, $withTax) * $weight;

        return $context->getCurrentLocale()->formatPrice($price, $context->currency->iso_code);
    }

    /** Usual names of the nutrition table rows; the "of which" rows are indented. */
    private const NUTRITION_NAMES = [
        'wartość energetyczna' => false, 'energy' => false, 'tłuszcz' => false, 'fat' => false,
        'w tym kwasy tłuszczowe nasycone' => true, 'of which saturates' => true,
        'węglowodany' => false, 'carbohydrate' => false, 'w tym cukry' => true, 'of which sugars' => true,
        'białko' => false, 'protein' => false, 'sól' => false, 'salt' => false,
    ];

    /**
     * The features named like nutrition table rows (only the "of which" rows with $sub), for the defaults.
     */
    public static function nutritionByNames(bool $sub): string
    {
        $ids = [];
        foreach (Feature::getFeatures((int) Configuration::get('PS_LANG_DEFAULT')) as $feature) {
            $name = mb_strtolower(trim((string) $feature['name']));
            if (isset(self::NUTRITION_NAMES[$name]) && (!$sub || self::NUTRITION_NAMES[$name])) {
                $ids[] = (int) $feature['id_feature'];
            }
        }

        return implode(',', $ids);
    }

    /**
     * The id of the first feature with one of the names, for the defaults of the settings.
     *
     * @param string[] $names lower-case names
     */
    public static function featureByName(array $names): int
    {
        foreach (Feature::getFeatures((int) Configuration::get('PS_LANG_DEFAULT')) as $feature) {
            if (in_array(mb_strtolower(trim((string) $feature['name'])), $names, true)) {
                return (int) $feature['id_feature'];
            }
        }

        return 0;
    }
}
