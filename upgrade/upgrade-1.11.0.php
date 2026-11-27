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
 * Cart section: after adding a product the mini cart opens unless another reaction is set.
 */
function upgrade_module_1_11_0(M4p_Core $module): bool
{
    if (M4pCoreSettings::get('CART_AFTER_ADD') === null) {
        M4pCoreSettings::set('CART_AFTER_ADD', 'minicart');
    }

    return true;
}
