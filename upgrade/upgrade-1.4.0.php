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
 * Leaves the compiled theme CSS with the token rule this version prints. The
 * `M4P_CORE_FONT_*` values stay in the database as they are, so a module that
 * takes over the typefaces finds them where they were.
 *
 * @param M4p_Core $module
 *
 * @return bool
 */
function upgrade_module_1_4_0($module)
{
    $shops = Shop::isFeatureActive() ? Shop::getCompleteListOfShopsID() : [null];

    foreach ($shops as $shop) {
        $idShop = $shop === null ? null : (int) $shop;
        $css = (string) M4pCoreSettings::get(M4pCoreTheme::CSS_KEY, '', $idShop);
        if ($css === '') {
            continue;
        }

        $root = strstr($css, ':root{');
        M4pCoreSettings::set(M4pCoreTheme::CSS_KEY, $root === false ? '' : $root, $idShop);
    }

    Configuration::deleteByName(M4pCoreSettings::PREFIX . 'THEME_PRELOAD');

    return $module->installFront();
}
