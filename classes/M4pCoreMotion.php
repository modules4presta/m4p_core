<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * How windows (dialogs, the cookie window, the import window) enter and leave. The settings become
 * the theme's `--modal-*` custom properties, which every window of the theme reads.
 */
class M4pCoreMotion
{
    /** Theme tokens these settings own; the theme sections leave them out. */
    public const TOKENS = ['modal-duration', 'modal-from'];

    private const FROM = [
        'fade' => 'none',
        'scale' => 'scale(0.96)',
        'slide' => 'translateY(24px)',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function section(Module $module): array
    {
        return [
            'title' => $module->trans('Windows', [], 'Modules.M4pcore.Admin'),
            'description' => $module->trans(
                'How windows open and close: the cookie window, the list and file import in the cart, the shopping list window.',
                [],
                'Modules.M4pcore.Admin'
            ),
            'icon' => 'icon-film',
            'owner' => 'm4p_core',
            'fields' => [
                ['key' => 'MODAL_ANIMATE', 'label' => $module->trans('Animate windows', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 1,
                    'hint' => $module->trans('Off: windows appear and disappear at once. Visitors who turned off animations in their system always see them at once.', [], 'Modules.M4pcore.Admin')],
                ['key' => 'MODAL_ANIMATION', 'label' => $module->trans('Animation', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                    'default' => 'scale', 'options' => [
                        'fade' => $module->trans('Fade', [], 'Modules.M4pcore.Admin'),
                        'scale' => $module->trans('Fade and grow', [], 'Modules.M4pcore.Admin'),
                        'slide' => $module->trans('Slide up', [], 'Modules.M4pcore.Admin'),
                    ]],
                ['key' => 'MODAL_DURATION', 'label' => $module->trans('Opening time in milliseconds', [], 'Modules.M4pcore.Admin'), 'type' => 'number',
                    'default' => 280, 'min' => 0, 'max' => 1000,
                    'hint' => $module->trans('Closing takes two thirds of this time.', [], 'Modules.M4pcore.Admin')],
            ],
        ];
    }

    /**
     * @return string the `<style>` tag for the page head, empty while the settings match the theme defaults
     */
    public static function head(): string
    {
        $animate = (bool) M4pCoreSettings::get('MODAL_ANIMATE', 1);
        $type = (string) M4pCoreSettings::get('MODAL_ANIMATION', 'scale');
        $duration = max(0, min(1000, (int) M4pCoreSettings::get('MODAL_DURATION', 280)));
        if ($animate && $type === 'scale' && $duration === 280) {
            return '';
        }

        $from = self::FROM[$type] ?? self::FROM['scale'];

        return '<style id="m4p-core-motion">:root{--modal-duration:' . ($animate ? $duration : 0) . 'ms;--modal-from:' . $from . '}</style>';
    }
}
