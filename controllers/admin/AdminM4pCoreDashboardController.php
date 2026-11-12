<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

class AdminM4pCoreDashboardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();
    }

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(_MODULE_DIR_ . 'm4p_core/views/css/admin.css');
    }

    public function initPageHeaderToolbar(): void
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_title = $this->trans('Overview', [], 'Modules.M4pcore.Admin');
        $this->page_header_toolbar_btn = [];
    }

    public function initContent(): void
    {
        parent::initContent();

        if (!$this->singleShop()) {
            return;
        }

        if (!Module::isEnabled($this->module->name)) {
            $this->warnings[] = $this->trans(
                'The module is disabled, so nothing reads these values right now.',
                [],
                'Modules.M4pcore.Admin'
            );
        }

        $sections = M4pCoreSections::all($this->module);

        $this->context->smarty->assign([
            'm4p_sections' => $this->sectionCards($sections),
            'm4p_modules' => $this->familyModules(),
            'm4p_state' => [
                'schema' => (int) Configuration::getGlobalValue(M4pCoreSettings::SCHEMA_KEY),
                'expected' => M4pCoreSettings::SCHEMA,
                'shop' => M4pCoreSettings::shopName(),
                'stored' => $this->filledCount($sections),
                'total' => $this->fieldCount($sections),
                'hook' => (int) Hook::getIdByName(M4p_Core::HOOK_SECTIONS) > 0,
            ],
            'm4p_settings_link' => $this->module->pageLink(M4p_Core::TAB_SETTINGS),
        ]);

        $this->content .= $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'm4p_core/views/templates/admin/dashboard.tpl'
        );

        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * How many values of the shop being edited are filled in.
     *
     * @param array<string, array<string, mixed>> $sections
     *
     * @return int
     */
    private function filledCount(array $sections): int
    {
        $filled = 0;
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                if ((string) M4pCoreSettings::get($field['key'], '') !== '') {
                    ++$filled;
                }
            }
        }

        return $filled;
    }

    /**
     * @param array<string, array<string, mixed>> $sections
     *
     * @return int
     */
    private function fieldCount(array $sections): int
    {
        $total = 0;
        foreach ($sections as $section) {
            $total += count($section['fields']);
        }

        return $total;
    }

    /**
     * The settings belong to one shop, so in the context of a group or of every
     * shop the page shows nothing and saves nothing.
     *
     * @return bool
     */
    protected function singleShop(): bool
    {
        if (!Shop::isFeatureActive() || Shop::getContext() === Shop::CONTEXT_SHOP) {
            return true;
        }

        $this->errors[] = $this->trans(
            'Pick a single shop in the multistore selector – these settings belong to one shop.',
            [],
            'Modules.M4pcore.Admin'
        );

        return false;
    }

    /**
     * How complete each section is, so the overview shows where work is left.
     *
     * @param array<string, array<string, mixed>> $sections
     *
     * @return array<int, array<string, mixed>>
     */
    private function sectionCards(array $sections): array
    {
        $cards = [];

        foreach ($sections as $id => $section) {
            $filled = 0;
            foreach ($section['fields'] as $field) {
                if ((string) M4pCoreSettings::get($field['key'], '') !== '') {
                    ++$filled;
                }
            }

            $cards[] = [
                'id' => $id,
                'title' => $section['title'],
                'description' => $section['description'],
                'icon' => $section['icon'],
                'owner' => $section['owner'],
                'filled' => $filled,
                'total' => count($section['fields']),
                'link' => $this->module->pageLink(M4p_Core::TAB_SETTINGS) . '&section=' . urlencode($id),
            ];
        }

        return $cards;
    }

    /**
     * Whether the module has a configuration page of its own, which is what the
     * link in the list points at.
     *
     * @param string $name
     *
     * @return bool
     */
    private function configurable(string $name): bool
    {
        try {
            $module = Module::getInstanceByName($name);
        } catch (Throwable $error) {
            return false;
        }

        return $module instanceof Module && method_exists($module, 'getContent');
    }

    /**
     * The modules of the family with the state the overview reports: installed,
     * enabled, and whether they add a section of their own.
     *
     * @return array<int, array<string, mixed>>
     */
    private function familyModules(): array
    {
        $idHook = (int) Hook::getIdByName(M4p_Core::HOOK_SECTIONS);

        $listening = [];
        if ($idHook > 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT m.`name` FROM `' . _DB_PREFIX_ . 'hook_module` hm
                 JOIN `' . _DB_PREFIX_ . 'module` m ON m.`id_module` = hm.`id_module`
                 WHERE hm.`id_hook` = ' . $idHook
            );
            $listening = array_column(is_array($rows) ? $rows : [], 'name');
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `name`, `version`, `active` FROM `' . _DB_PREFIX_ . 'module`
             WHERE `name` LIKE "m4p%" ORDER BY `name`'
        );

        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = (string) $row['name'];
            if ($name === 'm4p_core') {
                continue;
            }

            $out[] = [
                'name' => $name,
                'version' => (string) $row['version'],
                'active' => (bool) $row['active'],
                'listens' => in_array($name, $listening, true),
                'link' => $this->configurable($name)
                    ? $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $name])
                    : '',
            ];
        }

        return $out;
    }
}
