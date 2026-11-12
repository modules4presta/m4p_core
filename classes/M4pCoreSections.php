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
 * The sections of settings the back office shows. The module ships the ones every
 * shop needs; any other module adds its own on the `actionM4pCoreSections` hook,
 * which is what makes this a place to put shared settings rather than a fixed
 * list.
 */
class M4pCoreSections
{
    /**
     * @param Module $module for translating the built-in sections
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(Module $module): array
    {
        $sections = self::builtIn($module) + M4pCoreTheme::sections($module);

        Hook::exec('actionM4pCoreSections', ['sections' => &$sections]);

        return self::clean($sections);
    }

    /**
     * @param Module $module
     *
     * @return array<string, array<string, mixed>>
     */
    private static function builtIn(Module $module): array
    {
        return [
            'company' => [
                'title' => $module->trans('Company data', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Shown by the modules that print contact details, and used in structured data.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-building',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'COMPANY_NAME', 'label' => $module->trans('Legal name', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_ADDRESS', 'label' => $module->trans('Address', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea'],
                    ['key' => 'COMPANY_TAX_ID', 'label' => $module->trans('Tax number', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_PHONE', 'label' => $module->trans('Phone', [], 'Modules.M4pcore.Admin'), 'type' => 'tel'],
                    ['key' => 'COMPANY_EMAIL', 'label' => $module->trans('E-mail', [], 'Modules.M4pcore.Admin'), 'type' => 'email'],
                    ['key' => 'COMPANY_HOURS', 'label' => $module->trans('Opening hours', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('For example 7:30–16:00', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'measurement' => [
                'title' => $module->trans('Measurement', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Identifiers and switches the modules use when they report what visitors do.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-bar-chart',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'TRACK_GTM_ID', 'label' => $module->trans('Google Tag Manager container', [], 'Modules.M4pcore.Admin'),
                        'type' => 'text', 'placeholder' => 'GTM-XXXXXXX'],
                    ['key' => 'TRACK_EVENTS', 'label' => $module->trans('Report events', [], 'Modules.M4pcore.Admin'),
                        'type' => 'switch', 'default' => 1],
                    ['key' => 'TRACK_UTM_SOURCE', 'label' => $module->trans('utm_source for outbound links', [], 'Modules.M4pcore.Admin'),
                        'type' => 'text'],
                ],
            ],
            'links' => [
                'title' => $module->trans('Addresses', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans('Where the modules send a visitor who wants to know more.', [], 'Modules.M4pcore.Admin'),
                'icon' => 'icon-link',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'LINK_SHOP', 'label' => $module->trans('Module shop', [], 'Modules.M4pcore.Admin'), 'type' => 'url',
                        'placeholder' => 'https://'],
                    ['key' => 'LINK_SUPPORT', 'label' => $module->trans('Support page', [], 'Modules.M4pcore.Admin'), 'type' => 'url',
                        'placeholder' => 'https://'],
                ],
            ],
        ];
    }

    /**
     * Keeps only what the back office can render: a section needs a title and at
     * least one field, and a field needs a key and a known type. A section added
     * by another module cannot break this page by sending something else.
     *
     * @param array<int|string, mixed> $sections
     *
     * @return array<string, array<string, mixed>>
     */
    private static function clean(array $sections): array
    {
        $types = ['text', 'textarea', 'email', 'tel', 'url', 'number', 'switch', 'select', 'color', 'css', 'file'];
        $out = [];

        foreach ($sections as $id => $section) {
            $id = preg_replace('/[^a-z0-9_-]/i', '', (string) $id);
            if ($id === '' || !is_array($section) || trim((string) ($section['title'] ?? '')) === '') {
                continue;
            }

            $fields = [];
            foreach ((array) ($section['fields'] ?? []) as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $key = strtoupper(preg_replace('/[^A-Z0-9_]/i', '', (string) ($field['key'] ?? '')));
                $type = (string) ($field['type'] ?? 'text');
                if ($key === '' || !in_array($type, $types, true)) {
                    continue;
                }

                $field['key'] = $key;
                $field['type'] = $type;
                $field['label'] = trim((string) ($field['label'] ?? $key));
                $fields[] = $field;
            }

            if ($fields === []) {
                continue;
            }

            $out[$id] = [
                'title' => (string) $section['title'],
                'description' => (string) ($section['description'] ?? ''),
                'icon' => (string) ($section['icon'] ?? 'icon-cog'),
                'owner' => (string) ($section['owner'] ?? ''),
                'fields' => $fields,
            ];
        }

        return $out;
    }
}
