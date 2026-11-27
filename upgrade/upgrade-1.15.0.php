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

function upgrade_module_1_15_0(M4p_Core $module): bool
{
    return $module->registerHook('actionFrontControllerDetectContextCountryAfter');
}
