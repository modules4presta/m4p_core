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
 * Template data of the cart page: the sales unit of every line (from the unit feature of the Product page
 * section) and the VAT of the cart, which the core gives only with taxes shown in the cart.
 */
class M4pCoreCartView
{
    private const PACK_NAMES = ['opakowanie', 'packaging'];

    /**
     * @return array{lines: array<int, array{unit: string, weighed: bool, pack: string}>, vat: string}
     */
    public static function of(Context $context): array
    {
        $cart = $context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return ['lines' => [], 'vat' => ''];
        }

        $lines = [];
        foreach ($cart->getProducts() as $row) {
            $idProduct = (int) $row['id_product'];
            if (isset($lines[$idProduct])) {
                continue;
            }
            $view = M4pCoreProductView::of($context, $idProduct);
            $lines[$idProduct] = ['unit' => $view['unit'], 'weighed' => $view['weighed'], 'pack' => self::pack($context, $idProduct)];
        }

        $vat = (float) $cart->getOrderTotal(true) - (float) $cart->getOrderTotal(false);

        return [
            'lines' => $lines,
            'vat' => $context->getCurrentLocale()->formatPrice(max(0.0, $vat), $context->currency->iso_code),
        ];
    }

    /**
     * The packaging feature, found by name as on the product card.
     */
    private static function pack(Context $context, int $idProduct): string
    {
        foreach (Product::getFrontFeaturesStatic((int) $context->language->id, $idProduct) as $feature) {
            if (in_array(mb_strtolower(trim((string) $feature['name'])), self::PACK_NAMES, true)) {
                return (string) $feature['value'];
            }
        }

        return '';
    }
}
