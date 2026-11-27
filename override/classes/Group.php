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

class Group extends GroupCore
{
    /**
     * In the consumer view (m4p_core "Sales model") the storefront shows gross prices whatever the
     * customer's group says; the back office keeps the group's own setting.
     *
     * @param int $id_group
     *
     * @return int PS_TAX_EXC or PS_TAX_INC
     */
    public static function getPriceDisplayMethod($id_group)
    {
        if (!defined('_PS_ADMIN_DIR_') && Module::isEnabled('m4p_core')) {
            require_once _PS_MODULE_DIR_ . 'm4p_core/classes/M4pCoreSettings.php';
            require_once _PS_MODULE_DIR_ . 'm4p_core/classes/M4pCoreShopMode.php';
            if (M4pCoreShopMode::isB2c()) {
                return PS_TAX_INC;
            }
        }

        return parent::getPriceDisplayMethod($id_group);
    }
}
