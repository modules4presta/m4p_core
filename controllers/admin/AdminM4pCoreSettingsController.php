<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

class AdminM4pCoreSettingsController extends ModuleAdminController
{
    /** @var string[] keys of the fields that were refused */
    private $refused = [];

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
        $this->addJS(_MODULE_DIR_ . 'm4p_core/views/js/admin.js');
    }

    public function initPageHeaderToolbar(): void
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_title = $this->trans('Shared settings', [], 'Modules.M4pcore.Admin');
        $this->page_header_toolbar_btn = [];
    }

    public function postProcess(): void
    {
        if (!Tools::isSubmit('m4p_core_save')) {
            parent::postProcess();

            return;
        }

        if (!$this->singleShop()) {
            return;
        }

        if (!$this->access('edit')) {
            $this->errors[] = $this->trans('This account may not change these settings.', [], 'Modules.M4pcore.Admin');

            return;
        }

        $sections = M4pCoreSections::all($this->module);
        $id = (string) Tools::getValue('section');
        $section = $sections[$id] ?? null;

        if ($section === null) {
            $this->errors[] = $this->trans('Unknown section.', [], 'Modules.M4pcore.Admin');

            return;
        }

        $input = [];
        foreach ($section['fields'] as $field) {
            $input[$field['key']] = Tools::getValue($field['key']);
        }

        $result = M4pCoreSettings::saveSection($section, $input);
        $this->refused = $result['refused'];
        $changed = $this->alsoChanged($section, $result);

        if (strpos($id, 'theme_') === 0) {
            M4pCoreTheme::compile();
        }

        if ($result['refused'] === [] && $changed === []) {
            $this->confirmations[] = $this->trans('Settings saved.', [], 'Modules.M4pcore.Admin');

            return;
        }

        if ($result['refused'] !== []) {
            $this->warnings[] = sprintf(
                $this->trans('Saved, except these fields, which were left as they were: %s.', [], 'Modules.M4pcore.Admin'),
                implode(', ', $this->labelsOf($section, $result['refused']))
            );
        }

        if ($changed !== []) {
            $this->warnings[] = sprintf(
                $this->trans(
                    'These fields were stored without their tags or shortened to the length the field holds: %s.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                implode(', ', $this->labelsOf($section, $changed))
            );
        }
    }

    /**
     * PrestaShop takes the tags out of a value while the request is read, so the
     * difference is only visible against the raw content of `$_POST`.
     *
     * @param array<string, mixed> $section
     * @param array{refused: string[], changed: string[]} $result
     *
     * @return string[]
     */
    private function alsoChanged(array $section, array $result): array
    {
        $changed = $result['changed'];

        foreach ($section['fields'] as $field) {
            $key = (string) $field['key'];

            if (in_array($field['type'] ?? 'text', ['switch', 'file'], true) || in_array($key, $result['refused'], true)) {
                continue;
            }

            $raw = isset($_POST[$key]) && is_scalar($_POST[$key]) ? trim((string) $_POST[$key]) : '';

            if ($raw !== '' && (string) M4pCoreSettings::get($key, '') !== $raw && !in_array($key, $changed, true)) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    /**
     * @param array<string, mixed> $section
     * @param string[] $keys
     *
     * @return string[]
     */
    private function labelsOf(array $section, array $keys): array
    {
        $labels = [];
        foreach ($section['fields'] as $field) {
            if (in_array($field['key'], $keys, true)) {
                $labels[] = $field['label'];
            }
        }

        return $labels;
    }

    /**
     * The settings belong to one shop, so in the context of a group or of every
     * shop the page saves nothing and says so.
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
        if ($sections === []) {
            $this->errors[] = $this->trans('No section of settings is available.', [], 'Modules.M4pcore.Admin');

            return;
        }

        $id = (string) Tools::getValue('section');
        if (!isset($sections[$id])) {
            $id = (string) array_key_first($sections);
        }

        $section = $sections[$id];
        $values = M4pCoreSettings::ofSection($section);

        // A refused field shows the value that was sent, so it does not have to be typed again.
        foreach ($this->refused as $key) {
            $values[$key] = (string) Tools::getValue($key);
        }

        $swatches = [];
        foreach ($section['fields'] as $field) {
            if ($field['type'] !== 'color') {
                continue;
            }
            foreach ([(string) $values[$field['key']], (string) ($field['placeholder'] ?? '')] as $candidate) {
                if (preg_match('/^#[0-9a-f]{6}$/i', $candidate)) {
                    $swatches[$field['key']] = $candidate;
                    break;
                }
            }
        }

        $this->context->smarty->assign([
            'm4p_swatches' => $swatches,
            'm4p_sections' => $sections,
            'm4p_current' => $id,
            'm4p_section' => $section,
            'm4p_values' => $values,
            'm4p_base_link' => $this->module->pageLink(M4p_Core::TAB_SETTINGS),
            'm4p_shop' => M4pCoreSettings::shopName(),
            'm4p_refused' => $this->refused,
            'm4p_can_edit' => $this->access('edit'),
        ]);

        $this->content .= $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'm4p_core/views/templates/admin/settings.tpl'
        );

        $this->context->smarty->assign('content', $this->content);
    }
}
