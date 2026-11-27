<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sizes of a category image or thumbnail that the shop has not made yet. PrestaShop makes them on upload and on
 * "Regenerate thumbnails"; a theme enabled later brings its own sizes, so they are made on the first view instead.
 */
class M4pCoreCategoryImage
{
    /**
     * @param string $name file name without the extension: "12" or "12_thumb"
     * @param string $type image type, e.g. default_sm
     */
    public static function ensure(string $name, string $type): void
    {
        $source = _PS_CAT_IMG_DIR_ . $name . '.jpg';
        $target = _PS_CAT_IMG_DIR_ . $name . '-' . $type;
        if (!is_file($source) || is_file($target . '.jpg')) {
            return;
        }

        $imageType = ImageType::getByNameNType($type, 'categories');
        if (!is_array($imageType)) {
            return;
        }

        $formats = ['jpg'];
        if (in_array('webp', explode(',', (string) Configuration::get('PS_IMAGE_FORMAT')), true)) {
            $formats[] = 'webp';
        }
        foreach ($formats as $format) {
            if (!ImageManager::resize($source, $target . '.' . $format, (int) $imageType['width'], (int) $imageType['height'], $format)) {
                PrestaShopLogger::addLog('m4p_core: could not make ' . $name . '-' . $type . '.' . $format, 2, null, 'Category');
            }
        }
    }
}
