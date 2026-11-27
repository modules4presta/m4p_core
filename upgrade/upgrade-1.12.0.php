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
 * Content cards of the product page: picks the features by their usual names where nothing is set yet.
 */
function upgrade_module_1_12_0(M4p_Core $module): bool
{
    $defaults = [
        M4pCoreProductView::INGREDIENTS => M4pCoreProductView::featureByName(['składniki', 'ingredients']),
        M4pCoreProductView::ALLERGENS => M4pCoreProductView::featureByName(['alergeny', 'allergens']),
        M4pCoreProductView::FREE_FROM => M4pCoreProductView::featureByName(['nie zawiera', 'free from']),
        M4pCoreProductView::NUTRITION_BASIS => M4pCoreProductView::featureByName(['wartość odżywcza na', 'nutrition per']),
        M4pCoreProductView::NUTRITION => M4pCoreProductView::nutritionByNames(false),
        M4pCoreProductView::NUTRITION_SUB => M4pCoreProductView::nutritionByNames(true),
    ];
    foreach ($defaults as $key => $value) {
        if (M4pCoreSettings::get($key) === null) {
            M4pCoreSettings::set($key, $value);
        }
    }

    return true;
}
