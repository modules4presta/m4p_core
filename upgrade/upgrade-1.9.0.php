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
 * Size limit of product images: the hook after the core writes an image, and the default limits.
 */
function upgrade_module_1_9_0(M4p_Core $module): bool
{
    foreach ([M4pCoreImageLimit::MAX_KB => M4pCoreImageLimit::DEFAULT_MAX_KB, M4pCoreImageLimit::MAX_PX => M4pCoreImageLimit::DEFAULT_MAX_PX] as $key => $value) {
        if (M4pCoreSettings::get($key) === null) {
            M4pCoreSettings::set($key, $value);
        }
    }

    return $module->registerHook(M4p_Core::IMAGE_HOOKS);
}
