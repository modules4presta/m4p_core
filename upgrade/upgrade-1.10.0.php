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
 * Product page section: picks the features by their usual names where nothing is set yet.
 */
function upgrade_module_1_10_0(M4p_Core $module): bool
{
    $defaults = [
        M4pCoreProductView::ORIGIN => M4pCoreProductView::featureByName(['pochodzenie', 'origin']),
        M4pCoreProductView::UNIT => M4pCoreProductView::featureByName(['jednostka', 'unit']),
        M4pCoreProductView::factKey(1) => M4pCoreProductView::featureByName(['opakowanie', 'packaging']),
        M4pCoreProductView::iconKey(1) => 'package',
        M4pCoreProductView::factKey(2) => M4pCoreProductView::featureByName(['przechowywanie', 'storage']),
        M4pCoreProductView::iconKey(2) => 'snowflake',
        M4pCoreProductView::factKey(3) => M4pCoreProductView::featureByName(['termin przydatności', 'shelf life']),
        M4pCoreProductView::iconKey(3) => 'calendar',
    ];
    foreach ($defaults as $key => $value) {
        if (M4pCoreSettings::get($key) === null) {
            M4pCoreSettings::set($key, $value);
        }
    }

    return true;
}
