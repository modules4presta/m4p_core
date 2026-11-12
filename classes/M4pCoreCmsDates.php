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
 * Date of the last content change of each CMS page, which PrestaShop does not
 * keep. A save that leaves the content as it was does not move the date.
 */
class M4pCoreCmsDates
{
    private const TABLE = 'm4p_core_cms_date';

    /**
     * @return bool
     */
    public static function install(): bool
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
                `id_cms` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `content_hash` CHAR(32) NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_cms`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @param CMS $cms
     */
    public static function track(CMS $cms): void
    {
        if (!Validate::isLoadedObject($cms)) {
            return;
        }

        $content = is_array($cms->content) ? $cms->content : [(string) $cms->content];
        ksort($content);
        self::touch((int) $cms->id, md5(implode("\0", $content)));
    }

    /**
     * Date of the last change, recorded on the first call for a page that has none yet.
     *
     * @param int $idCms
     *
     * @return string Y-m-d
     */
    public static function of(int $idCms): string
    {
        $idShop = (int) Context::getContext()->shop->id;
        $date = Db::getInstance()->getValue(
            'SELECT `date_upd` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_cms` = ' . $idCms . ' AND `id_shop` = ' . $idShop
        );

        if (!$date) {
            self::track(new CMS($idCms));
            $date = Db::getInstance()->getValue(
                'SELECT `date_upd` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_cms` = ' . $idCms . ' AND `id_shop` = ' . $idShop
            );
        }

        return substr((string) $date, 0, 10);
    }

    /**
     * @param int $idCms
     * @param string $hash
     */
    private static function touch(int $idCms, string $hash): void
    {
        $idShop = (int) Context::getContext()->shop->id;
        $db = Db::getInstance();
        $stored = $db->getValue(
            'SELECT `content_hash` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_cms` = ' . $idCms . ' AND `id_shop` = ' . $idShop
        );

        if ($stored === $hash) {
            return;
        }

        // The date comes from the database: PHP and MariaDB can sit in different time
        // zones, and a date written from PHP is then shifted against NOW().
        $db->execute(
            'REPLACE INTO `' . _DB_PREFIX_ . self::TABLE . '` (`id_cms`, `id_shop`, `content_hash`, `date_upd`)'
            . ' VALUES (' . $idCms . ', ' . $idShop . ', \'' . pSQL($hash) . '\', NOW())'
        );
    }
}
