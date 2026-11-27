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
 * The sales department starts with the phone and weekly hours the contact widget already shows.
 */
function upgrade_module_1_8_0(M4p_Core $module): bool
{
    $copies = [
        M4pCoreSales::PHONE => 'M4P_CONTACTWIDGET_PHONE',
        M4pCoreSales::HOURS => 'M4P_CONTACTWIDGET_SCHEDULE',
    ];
    foreach (Shop::getShops(false, null, true) as $idShop) {
        foreach ($copies as $key => $source) {
            $value = trim((string) Configuration::get($source, null, null, (int) $idShop));
            if ($value !== '' && (string) M4pCoreSettings::get($key, '', (int) $idShop) === '') {
                M4pCoreSettings::set($key, $value, (int) $idShop);
            }
        }
    }

    return true;
}
