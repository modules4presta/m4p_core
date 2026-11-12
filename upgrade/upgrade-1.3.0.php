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
 * Splits the stored CMS page layouts per shop.
 *
 * @param M4p_Core $module
 *
 * @return bool
 */
function upgrade_module_1_3_0($module)
{
    $table = _DB_PREFIX_ . 'm4p_core_cms_layout';
    $db = Db::getInstance();

    if (!$db->executeS('SHOW TABLES LIKE "' . pSQL($table) . '"')) {
        return true;
    }

    if ($db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "id_shop"')) {
        return true;
    }

    $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
    if ($idShop <= 0) {
        $idShop = (int) Context::getContext()->shop->id;
    }

    return $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD `id_shop` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id_cms`')
        && $db->execute('UPDATE `' . bqSQL($table) . '` SET `id_shop` = ' . $idShop . ' WHERE `id_shop` = 0')
        && $db->execute('ALTER TABLE `' . bqSQL($table) . '` DROP PRIMARY KEY, ADD PRIMARY KEY (`id_cms`, `id_shop`)')
        && $db->execute('ALTER TABLE `' . bqSQL($table) . '` ALTER `id_shop` DROP DEFAULT');
}
