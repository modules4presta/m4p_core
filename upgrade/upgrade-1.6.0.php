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
 * Fills the "Delivery" section with the cut-off and delivery days stored by
 * m4p_homeintro, or with the defaults, and the browser tab title with the one
 * stored by m4p_themesettings. Values already set are left alone.
 *
 * @param M4p_Core $module
 *
 * @return bool
 */
function upgrade_module_1_6_0($module)
{
    $shops = Shop::isFeatureActive() ? Shop::getCompleteListOfShopsID() : [null];
    $keys = ['ORDER_CUTOFF' => ['M4P_HI_CUTOFF', '14:00'], 'DELIVERY_DAYS' => ['M4P_HI_DAYS', '1,2,3,4,5']];

    foreach ($shops as $shop) {
        $idShop = $shop === null ? null : (int) $shop;
        foreach ($keys as $key => [$legacyKey, $default]) {
            if (M4pCoreSettings::get($key, null, $idShop) !== null) {
                continue;
            }
            $legacy = (string) Configuration::get($legacyKey, null, null, $idShop);
            M4pCoreSettings::set($key, $legacy !== '' ? $legacy : $default, $idShop);
        }
        if (M4pCoreSettings::get('TAB_BLUR_TITLE', null, $idShop) === null) {
            $legacy = (string) Configuration::get('M4P_THEME_TAB_BLUR_TITLE', (int) Configuration::get('PS_LANG_DEFAULT'), null, $idShop);
            M4pCoreSettings::set('TAB_BLUR_TITLE', $legacy, $idShop);
        }
    }

    return true;
}
