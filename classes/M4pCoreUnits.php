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
 * A count with the sale unit in the right grammatical form: 1 karton, 3 kartony, 198 kartonów. Units outside the
 * dictionary (kg, abbreviations such as op. or szt.) and fractional counts keep the unit as written. In templates:
 * {$quantity|m4p_units:$unit}, and after "from" (genitive: od 1 kartonu, od 5 kartonów) {$quantity|m4p_units:$unit:'from'}.
 */
class M4pCoreUnits
{
    /** Singular, the form after 2–4, the form after 5 and more; then the genitive singular for "from 1". */
    private const FORMS = [
        'karton' => ['karton', 'kartony', 'kartonów', 'kartonu'],
        'skrzynka' => ['skrzynka', 'skrzynki', 'skrzynek', 'skrzynki'],
        'worek' => ['worek', 'worki', 'worków', 'worka'],
        'opakowanie' => ['opakowanie', 'opakowania', 'opakowań', 'opakowania'],
        'sztuka' => ['sztuka', 'sztuki', 'sztuk', 'sztuki'],
        'paleta' => ['paleta', 'palety', 'palet', 'palety'],
        'butelka' => ['butelka', 'butelki', 'butelek', 'butelki'],
        'puszka' => ['puszka', 'puszki', 'puszek', 'puszki'],
        'wiadro' => ['wiadro', 'wiadra', 'wiader', 'wiadra'],
        'kanister' => ['kanister', 'kanistry', 'kanistrów', 'kanistra'],
        'tacka' => ['tacka', 'tacki', 'tacek', 'tacki'],
        'produkt' => ['produkt', 'produkty', 'produktów', 'produktu'],
        'użycie' => ['użycie', 'użycia', 'użyć', 'użycia'],
        'dzień' => ['dzień', 'dni', 'dni', 'dnia'],
        'zapytanie' => ['zapytanie', 'zapytania', 'zapytań', 'zapytania'],
        'pozycja' => ['pozycja', 'pozycje', 'pozycji', 'pozycji'],
        'zamówienie' => ['zamówienie', 'zamówienia', 'zamówień', 'zamówienia'],
        'nowa wiadomość' => ['nowa wiadomość', 'nowe wiadomości', 'nowych wiadomości', 'nowej wiadomości'],
    ];

    /**
     * @param int|float|string $quantity
     */
    public static function count($quantity, string $unit = '', string $case = ''): string
    {
        $unit = trim($unit);
        $number = (string) $quantity;

        return $unit === '' ? $number : $number . ' ' . self::word($quantity, $unit, $case);
    }

    /**
     * @param int|float|string $quantity
     */
    public static function word($quantity, string $unit, string $case = ''): string
    {
        $forms = self::FORMS[mb_strtolower(trim($unit))] ?? null;
        if ($forms === null || !is_numeric($quantity) || (float) $quantity != (int) $quantity) {
            return $unit;
        }
        $n = abs((int) $quantity);
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($case === 'from') {
            return $n === 1 ? $forms[3] : $forms[2];
        }
        if ($n === 1) {
            return $forms[0];
        }

        return $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) ? $forms[1] : $forms[2];
    }

    /**
     * A no-break space between a number and the unit after it ("125 g", "12 × 1 l"), so a name never breaks
     * before the unit. In templates: {$name|m4p_nbsp}.
     */
    public static function noBreak(string $text): string
    {
        return (string) preg_replace('/(\d(?:[,.]\d+)?)\s+(?=(?:×|x|kg|dag|mg|g|ml|cl|l|szt\.?|op\.|cm|mm|m|%)(?![\p{L}\d]))/u', "$1\u{00A0}", $text);
    }

    /** Registers the {$quantity|m4p_units:$unit} and {$name|m4p_nbsp} modifiers once per request. */
    public static function registerModifier(Smarty $smarty): void
    {
        if (!isset($smarty->registered_plugins['modifier']['m4p_units'])) {
            $smarty->registerPlugin('modifier', 'm4p_units', [self::class, 'count']);
        }
        if (!isset($smarty->registered_plugins['modifier']['m4p_nbsp'])) {
            $smarty->registerPlugin('modifier', 'm4p_nbsp', [self::class, 'noBreak']);
        }
        if (!isset($smarty->registered_plugins['modifier']['m4p_price'])) {
            $smarty->registerPlugin('modifier', 'm4p_price', [self::class, 'price']);
        }
    }

    /**
     * An amount in the currency and number format of the visitor: {$amount|m4p_price}.
     *
     * @param int|float|string $amount
     */
    public static function price($amount): string
    {
        $context = Context::getContext();

        return $context->getCurrentLocale()->formatPrice((float) $amount, (string) $context->currency->iso_code);
    }
}
