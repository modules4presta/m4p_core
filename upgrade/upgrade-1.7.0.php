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
 * Theme variables, the second price line and the gross-price override of Group.
 */
function upgrade_module_1_7_0(M4p_Core $module): bool
{
    return $module->installFront() && $module->installOverrides();
}
