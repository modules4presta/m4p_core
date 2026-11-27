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
 * "The shop sells to" defaults to consumers on a new install; a shop without a
 * stored value keeps the company view it shows.
 */
function upgrade_module_1_25_0(M4p_Core $module): bool
{
    $key = M4pCoreSettings::PREFIX . 'SHOP_MODE';
    if (!Configuration::hasKey($key)) {
        Configuration::updateGlobalValue($key, M4pCoreShopMode::B2B);
    }

    return true;
}
