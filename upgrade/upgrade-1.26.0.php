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
 * The product image limit is off on a new install; a shop without a stored
 * value keeps the 200 KB limit it applies.
 */
function upgrade_module_1_26_0(M4p_Core $module): bool
{
    if (M4pCoreSettings::get(M4pCoreImageLimit::MAX_KB) === null) {
        M4pCoreSettings::set(M4pCoreImageLimit::MAX_KB, 200);
    }

    return true;
}
