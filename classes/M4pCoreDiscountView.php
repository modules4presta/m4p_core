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
 * Template data of the account's discount codes page ($m4p_discounts): the codes the customer can use now, each with
 * its value, the conditions as short facts and the number of days left when the code ends within a week.
 */
class M4pCoreDiscountView
{
    /** Codes ending within this many days get the "ends in" label. */
    public const ENDING_DAYS = 7;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function of(Context $context): array
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest) {
            return [];
        }
        $idCustomer = (int) $customer->id;
        $locale = $context->getCurrentLocale();
        $today = new DateTimeImmutable('today');
        $codes = [];
        foreach (CartRule::getCustomerCartRules((int) $context->language->id, $idCustomer, true, false) as $rule) {
            if ((int) $rule['id_customer'] !== 0 && (int) $rule['id_customer'] !== $idCustomer) {
                continue;
            }
            if ((int) $rule['quantity'] === 0 || (int) $rule['quantity_for_user'] === 0) {
                continue;
            }
            $iso = (string) Currency::getIsoCodeById((int) $rule['reduction_currency'] ?: (int) $context->currency->id);
            $minimumIso = (string) Currency::getIsoCodeById((int) $rule['minimum_amount_currency'] ?: (int) $context->currency->id);
            $ends = new DateTimeImmutable((string) $rule['date_to']);
            $daysLeft = (int) $today->diff($ends->setTime(0, 0))->format('%r%a');

            $codes[] = [
                'code' => (string) $rule['code'],
                'name' => (string) $rule['name'],
                'kind' => self::kind($rule),
                'value' => self::value($locale, $rule, $iso),
                'value_net' => (float) $rule['reduction_amount'] > 0 && !(bool) $rule['reduction_tax'],
                'free_shipping' => (bool) $rule['free_shipping'],
                'minimum' => (float) $rule['minimum_amount'] > 0 ? $locale->formatPrice((float) $rule['minimum_amount'], $minimumIso) : '',
                'minimum_net' => !(bool) $rule['minimum_amount_tax'],
                'ends' => $ends->format('d.m.Y'),
                'days_left' => $daysLeft >= 0 && $daysLeft <= self::ENDING_DAYS ? $daysLeft : null,
                'uses_left' => (int) $rule['quantity_for_user'],
                'combinable' => !(bool) $rule['cart_rule_restriction'],
            ];
        }

        return $codes;
    }

    /**
     * percent, amount or shipping: what the large value of the code is.
     *
     * @param array<string, mixed> $rule
     */
    private static function kind(array $rule): string
    {
        if ((float) $rule['reduction_percent'] > 0) {
            return 'percent';
        }

        return (float) $rule['reduction_amount'] > 0 ? 'amount' : 'shipping';
    }

    /**
     * @param array<string, mixed> $rule
     */
    private static function value($locale, array $rule, string $iso): string
    {
        if ((float) $rule['reduction_percent'] > 0) {
            return $locale->formatNumber((float) $rule['reduction_percent']) . '%';
        }
        if ((float) $rule['reduction_amount'] > 0) {
            return $locale->formatPrice((float) $rule['reduction_amount'], $iso);
        }

        return '';
    }
}
