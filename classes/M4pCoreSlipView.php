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
 * Template data of the account's invoice corrections page ($m4p_slips), by credit slip id: the number as printed on
 * the PDF, the gross amount of the correction and its date.
 */
class M4pCoreSlipView
{
    /**
     * @return array<int, array{number: string, amount: string, date: string}>
     */
    public static function of(Context $context): array
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest) {
            return [];
        }
        $locale = $context->getCurrentLocale();
        $prefix = (string) Configuration::get('PS_CREDIT_SLIP_PREFIX', (int) $context->language->id);
        $slips = [];
        foreach (OrderSlip::getOrdersSlip((int) $customer->id) ?: [] as $row) {
            $order = new Order((int) $row['id_order']);
            $iso = (string) Currency::getIsoCodeById((int) $order->id_currency ?: (int) $context->currency->id);
            $gross = (float) $row['total_products_tax_incl'] + (float) $row['total_shipping_tax_incl'];
            $slips[(int) $row['id_order_slip']] = [
                'number' => $prefix . sprintf('%06d', (int) $row['id_order_slip']),
                'amount' => $locale->formatPrice($gross, $iso),
                'date' => date('d.m.Y', strtotime((string) $row['date_add']) ?: time()),
            ];
        }

        return $slips;
    }
}
