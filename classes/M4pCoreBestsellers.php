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

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;

/**
 * Most ordered products for the "no results" and 404 pages ($m4p_bestsellers), presented like a product list.
 */
class M4pCoreBestsellers
{
    /**
     * @return array<int, mixed> presented products, empty when the shop has no sales yet
     */
    public static function presented(Context $context, int $limit): array
    {
        $rows = ProductSale::getBestSales((int) $context->language->id, 0, $limit, 'quantity', 'DESC');
        if (!is_array($rows) || !$rows) {
            return [];
        }

        $assembler = new ProductAssembler($context);
        $presenterFactory = new ProductPresenterFactory($context);
        $settings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever($context->link),
            $context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $context->getTranslator()
        );

        $products = [];
        foreach ($rows as $row) {
            $products[] = $presenter->present($settings, $assembler->assembleProduct($row), $context->language);
        }

        return $products;
    }
}
