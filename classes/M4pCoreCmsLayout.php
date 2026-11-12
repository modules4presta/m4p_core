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
 * Layout chosen for each CMS page in its back-office form, stored per shop. The theme
 * decides what each value looks like; a page without a stored choice is `auto`.
 */
class M4pCoreCmsLayout
{
    public const AUTO = 'auto';
    public const DOCUMENT = 'document';
    public const PAGE = 'page';

    public const FIELD = 'm4p_core_layout';

    private const TABLE = 'm4p_core_cms_layout';

    /**
     * @return bool
     */
    public static function install(): bool
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
                `id_cms` INT UNSIGNED NOT NULL,
                `id_shop` INT UNSIGNED NOT NULL,
                `layout` VARCHAR(16) NOT NULL,
                PRIMARY KEY (`id_cms`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @param int $idCms
     *
     * @return string one of the class constants
     */
    public static function of(int $idCms): string
    {
        $layout = (string) Db::getInstance()->getValue(
            'SELECT `layout` FROM `' . _DB_PREFIX_ . self::TABLE . '`'
            . ' WHERE `id_cms` = ' . $idCms . ' AND `id_shop` = ' . self::shop()
        );

        return in_array($layout, [self::DOCUMENT, self::PAGE], true) ? $layout : self::AUTO;
    }

    /**
     * Stores the choice; `auto` and unknown values remove the row.
     *
     * @param int $idCms
     * @param string $layout
     */
    public static function save(int $idCms, string $layout): void
    {
        if ($idCms <= 0) {
            return;
        }

        if (!in_array($layout, [self::DOCUMENT, self::PAGE], true)) {
            self::delete($idCms);

            return;
        }

        Db::getInstance()->insert(
            self::TABLE,
            ['id_cms' => $idCms, 'id_shop' => self::shop(), 'layout' => pSQL($layout)],
            false,
            true,
            Db::REPLACE
        );
    }

    /**
     * @param int $idCms
     */
    public static function delete(int $idCms): void
    {
        Db::getInstance()->delete(self::TABLE, '`id_cms` = ' . $idCms . ' AND `id_shop` = ' . self::shop());
    }

    /**
     * @return int
     */
    private static function shop(): int
    {
        return (int) Context::getContext()->shop->id;
    }

    /**
     * Adds the choice field to the CMS page form of the back office.
     *
     * @param Module $module
     * @param array<string, mixed> $params parameters of `actionCmsPageFormBuilderModifier`
     */
    public static function addField(Module $module, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $params['form_builder']->add(self::FIELD, 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', [
            'label' => $module->trans('Page layout', [], 'Modules.M4pcore.Admin'),
            'help' => $module->trans('The document layout shows a table of contents built from the H2 headings next to the content. The theme has to support it.', [], 'Modules.M4pcore.Admin'),
            'choices' => [
                $module->trans('Automatic: document when the page has at least two H2 headings', [], 'Modules.M4pcore.Admin') => self::AUTO,
                $module->trans('Document with a table of contents', [], 'Modules.M4pcore.Admin') => self::DOCUMENT,
                $module->trans('Regular page', [], 'Modules.M4pcore.Admin') => self::PAGE,
            ],
            'data' => $id > 0 ? self::of($id) : self::AUTO,
            'required' => false,
            'placeholder' => false,
        ]);
    }
}
