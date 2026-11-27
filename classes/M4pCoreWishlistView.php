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
 * Template data of the account's shopping lists page ($m4p_wishlists): the lists of blockwishlist that belong to the
 * signed-in customer, each with its products, quantities and prices in the price display of the customer. A product
 * sold by weight is priced per kilogram; its line value is estimated from the product weight (one pack).
 */
class M4pCoreWishlistView
{
    public const MAX_QUANTITY = 99999;

    /**
     * @return array<string, mixed>
     */
    public static function of(Context $context): array
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest) {
            return [];
        }

        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $rows = $db->executeS(
            'SELECT `id_wishlist`, `name`, `date_upd` FROM `' . _DB_PREFIX_ . 'wishlist`'
            . ' WHERE `id_customer` = ' . (int) $customer->id . ' AND `id_shop` = ' . (int) $context->shop->id
            . ' ORDER BY `id_wishlist`'
        ) ?: [];

        $withTax = Product::getTaxCalculationMethod((int) $customer->id) !== PS_TAX_EXC;
        $lists = [];
        foreach ($rows as $row) {
            $idWishlist = (int) $row['id_wishlist'];
            $products = self::products($context, $idWishlist, $withTax);
            $lists[] = [
                'id' => $idWishlist,
                'name' => (string) $row['name'],
                'date' => date('d.m.Y', strtotime((string) $row['date_upd']) ?: time()),
                'count' => count($products),
                'products' => $products,
            ];
        }

        return [
            'quantity_url' => $context->link->getModuleLink('m4p_core', 'wishlistquantity', [], true),
            'net' => !$withTax,
            'currency' => (string) $context->currency->iso_code,
            'lists' => $lists,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function products(Context $context, int $idWishlist, bool $withTax): array
    {
        $idLang = (int) $context->language->id;
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT wp.`id_product`, wp.`id_product_attribute`, wp.`quantity`, pl.`name`, pl.`link_rewrite`, p.`reference`,'
            . ' p.`weight`, IF(wp.`id_product_attribute` > 0, pa.`minimal_quantity`, p.`minimal_quantity`) AS `minimal_quantity`'
            . ' FROM `' . _DB_PREFIX_ . 'wishlist_product` wp'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = wp.`id_product`'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = wp.`id_product` AND pl.`id_lang` = ' . $idLang
            . ' AND pl.`id_shop` = ' . (int) $context->shop->id
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.`id_product_attribute` = wp.`id_product_attribute`'
            . ' WHERE wp.`id_wishlist` = ' . $idWishlist
            . ' ORDER BY wp.`id_wishlist_product`'
        ) ?: [];

        $locale = $context->getCurrentLocale();
        $iso = (string) $context->currency->iso_code;
        $products = [];
        foreach ($rows as $row) {
            $idProduct = (int) $row['id_product'];
            $idAttribute = (int) $row['id_product_attribute'];
            $view = M4pCoreProductView::of($context, $idProduct);
            $price = (float) Product::getPriceStatic($idProduct, $withTax, $idAttribute ?: null);
            $weight = (float) $row['weight'];
            $estimated = $view['weighed'] && $weight > 0;
            $image = self::image($context, $idProduct, $idAttribute, (string) $row['link_rewrite']);
            $quantity = max(1, (int) $row['quantity']);

            $products[] = [
                'id_product' => $idProduct,
                'id_product_attribute' => $idAttribute,
                'name' => $idAttribute ? (string) Product::getProductName($idProduct, $idAttribute, $idLang) : (string) $row['name'],
                'reference' => (string) $row['reference'],
                'url' => $context->link->getProductLink($idProduct, null, null, null, $idLang, null, $idAttribute),
                'image' => $image,
                'image_webp' => $image !== '' ? (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $image) : '',
                'unit' => $view['unit'],
                'weighed' => $view['weighed'],
                'price' => $locale->formatPrice($price, $iso),
                // the amount one unit of the stepper adds: a weighed pack at its average weight
                'step_amount' => round($estimated ? $price * $weight : $price, 6),
                'estimated' => $estimated,
                'quantity' => $quantity,
                'min' => max(1, (int) $row['minimal_quantity']),
                'value' => $locale->formatPrice(($estimated ? $price * $weight : $price) * $quantity, $iso),
                'available' => self::available($idProduct, $idAttribute),
            ];
        }

        return $products;
    }

    /**
     * Sets the quantity of a product already on a list of the signed-in customer; false for a list or product that
     * is not there.
     */
    public static function setQuantity(Context $context, int $idWishlist, int $idProduct, int $idAttribute, int $quantity): bool
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest || $quantity < 1 || $quantity > self::MAX_QUANTITY) {
            return false;
        }
        $db = Db::getInstance();
        $where = '`id_wishlist` = ' . $idWishlist . ' AND `id_product` = ' . $idProduct . ' AND `id_product_attribute` = ' . $idAttribute;
        $owned = (bool) $db->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'wishlist` w INNER JOIN `' . _DB_PREFIX_ . 'wishlist_product` wp ON wp.`id_wishlist` = w.`id_wishlist`'
            . ' WHERE w.`id_wishlist` = ' . $idWishlist . ' AND w.`id_customer` = ' . (int) $customer->id
            . ' AND w.`id_shop` = ' . (int) $context->shop->id
            . ' AND wp.`id_product` = ' . $idProduct . ' AND wp.`id_product_attribute` = ' . $idAttribute
        );
        if (!$owned) {
            return false;
        }

        return $db->update('wishlist_product', ['quantity' => $quantity], $where)
            && $db->update('wishlist', ['date_upd' => ['type' => 'sql', 'value' => 'NOW()']], '`id_wishlist` = ' . $idWishlist);
    }

    private static function image(Context $context, int $idProduct, int $idAttribute, string $rewrite): string
    {
        $cover = $idAttribute ? Image::getBestImageAttribute((int) $context->shop->id, (int) $context->language->id, $idProduct, $idAttribute) : null;
        if (!$cover) {
            $cover = Product::getCover($idProduct);
        }
        if (!$cover) {
            return '';
        }

        return $context->link->getImageLink($rewrite, (string) $cover['id_image'], 'cart_default');
    }

    /**
     * Whether the product can go to the cart now: active, sold and in stock or orderable out of stock.
     */
    private static function available(int $idProduct, int $idAttribute): bool
    {
        $product = new Product($idProduct);
        if (!Validate::isLoadedObject($product) || !$product->active || !$product->available_for_order) {
            return false;
        }
        if (StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute ?: null) > 0) {
            return true;
        }

        return (bool) Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($idProduct));
    }
}
