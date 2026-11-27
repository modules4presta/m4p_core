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
 * Data of the category page header ($m4p_category): the cover photo when it is wide enough for the header,
 * the icon of each subcategory and the number of products in every active category.
 */
class M4pCoreCategoryView
{
    /** A cover at least this much wider than tall is a header photo; a squarer one is an icon. */
    private const PHOTO_RATIO = 1.6;

    /** Width of the header copy of the cover: the container at 2x on phones, about 1x on wide screens. */
    private const HEADER_WIDTH = 1600;

    /**
     * @return array{hero: string, icons: array<int, string>, icons_webp: array<int, string>, counts: array<int, int>, tiles: int[]}
     */
    public static function of(Context $context, int $idCategory): array
    {
        $category = new Category($idCategory, (int) $context->language->id);
        $counts = self::counts($context);
        $icons = [];
        $iconsWebp = [];
        $stocked = [];
        foreach (Category::getChildren($idCategory, (int) $context->language->id, true, (int) $context->shop->id) as $child) {
            $id = (int) $child['id_category'];
            $icon = self::icon($context, $id, (string) $child['link_rewrite']);
            if ($icon['jpg'] !== '') {
                $icons[$id] = $icon['jpg'];
            }
            if ($icon['webp'] !== '') {
                $iconsWebp[$id] = $icon['webp'];
            }
            if (($counts[$id] ?? 0) > 0) {
                $stocked[] = $id;
            }
        }

        return [
            'hero' => Validate::isLoadedObject($category) ? self::photo($idCategory) : '',
            'icons' => $icons,
            'icons_webp' => $iconsWebp,
            'counts' => $counts,
            'tiles' => self::tiles($stocked, $counts, (int) M4pCoreSettings::get('CATEGORY_TILES', 4)),
        ];
    }

    /**
     * URL of the cover when it is a wide photo, '' otherwise: a WebP copy at most HEADER_WIDTH wide, made once
     * per cover, or the original when that copy cannot be written.
     */
    private static function photo(int $idCategory): string
    {
        $file = _PS_CAT_IMG_DIR_ . $idCategory . '.jpg';
        $size = is_file($file) ? @getimagesize($file) : false;
        if (!$size || $size[1] <= 0 || $size[0] / $size[1] < self::PHOTO_RATIO) {
            return '';
        }

        $name = $idCategory . '-m4p_header.webp';
        $copy = _PS_CAT_IMG_DIR_ . $name;
        if (!is_file($copy) || filemtime($copy) < filemtime($file)) {
            $width = min(self::HEADER_WIDTH, (int) $size[0]);
            $height = (int) round($width * $size[1] / $size[0]);
            if (!ImageManager::resize($file, $copy, $width, $height, 'webp')) {
                return _THEME_CAT_DIR_ . $idCategory . '.jpg?' . filemtime($file);
            }
        }

        return _THEME_CAT_DIR_ . $name . '?' . filemtime($copy);
    }

    /**
     * Thumbnail of the category, or its cover when that is not a header photo; the WebP copy when the shop makes one.
     *
     * @return array{jpg: string, webp: string}
     */
    private static function icon(Context $context, int $idCategory, string $rewrite): array
    {
        $none = ['jpg' => '', 'webp' => ''];
        foreach ([$idCategory . '_thumb', (string) $idCategory] as $name) {
            $file = _PS_CAT_IMG_DIR_ . $name . '.jpg';
            if (!is_file($file)) {
                continue;
            }
            if ($name === (string) $idCategory && self::photo($idCategory) !== '') {
                return $none;
            }
            M4pCoreCategoryImage::ensure($name, 'default_sm');
            $webp = in_array('webp', explode(',', (string) Configuration::get('PS_IMAGE_FORMAT')), true)
                && is_file(_PS_CAT_IMG_DIR_ . $name . '-default_sm.webp');

            return [
                'jpg' => $context->link->getCatImageLink($rewrite, $name, 'default_sm'),
                'webp' => $webp ? $context->link->getCatImageLink($rewrite, $name, 'default_sm', 'webp') : '',
            ];
        }

        return $none;
    }

    /**
     * Ids of the subcategories shown as tiles: the ones with the most products, in the order of the menu on a tie.
     *
     * @param int[] $stocked children with products, in menu order
     * @param array<int, int> $counts
     *
     * @return int[]
     */
    private static function tiles(array $stocked, array $counts, int $limit): array
    {
        $order = array_flip($stocked);
        usort($stocked, static fn (int $a, int $b): int => [$counts[$b], $order[$a]] <=> [$counts[$a], $order[$b]]);

        return array_slice($stocked, 0, max(0, $limit));
    }

    /**
     * Active products visible in the catalogue, per category they are assigned to.
     *
     * @return array<int, int>
     */
    private static function counts(Context $context): array
    {
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT cp.`id_category`, COUNT(DISTINCT cp.`id_product`) AS `n`
            FROM `' . _DB_PREFIX_ . 'category_product` cp
            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = cp.`id_product` AND ps.`id_shop` = ' . (int) $context->shop->id . '
            WHERE ps.`active` = 1 AND ps.`visibility` IN (\'both\', \'catalog\')
            GROUP BY cp.`id_category`'
        ) ?: [];
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['id_category']] = (int) $row['n'];
        }

        return $counts;
    }
}
