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
 * The look of the active theme as settings: every CSS custom property the theme
 * declares in its tokens file becomes a field, grouped into sections. A value
 * left empty keeps the theme's own, a filled one is printed in the page head
 * after the theme's styles.
 */
class M4pCoreTheme
{
    public const TOKENS_PATH_KEY = 'THEME_TOKENS_PATH';
    public const DEFAULT_TOKENS_PATH = 'assets/css/custom/tokens.css';
    public const CSS_KEY = 'THEME_CSS';
    public const KEY_PREFIX = 'THEME_';

    /** Tokens of the typefaces: the theme declares them, this module leaves them alone. */
    public const FONT_TOKENS = ['font-display', 'font-body'];

    /** Section id => [title, icon], in menu order. */
    private const GROUPS = [
        'theme_colors' => ['Theme: colors', 'icon-tint'],
        'theme_typography' => ['Theme: typography', 'icon-font'],
        'theme_layout' => ['Theme: layout and spacing', 'icon-columns'],
        'theme_shape' => ['Theme: shape and effects', 'icon-square-o'],
    ];

    /**
     * Sections built from the theme's tokens, or nothing when the theme has no tokens file.
     *
     * @param Module $module
     *
     * @return array<string, array<string, mixed>>
     */
    public static function sections(Module $module): array
    {
        $tokens = self::tokens();
        if ($tokens === []) {
            return [];
        }

        $fields = array_fill_keys(array_keys(self::GROUPS), []);
        foreach ($tokens as $name => $default) {
            if (in_array($name, self::FONT_TOKENS, true)) {
                continue;
            }
            $fields[self::groupOf($name, $default)][] = [
                'key' => self::keyOf($name),
                'label' => '--' . $name,
                'type' => self::isColor($default) ? 'color' : 'css',
                'placeholder' => $default,
                'token' => $name,
            ];
        }

        $sections = [];
        foreach (self::GROUPS as $id => [$title, $icon]) {
            if ($fields[$id] === []) {
                continue;
            }
            $sections[$id] = [
                'title' => $module->trans($title, [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Values of the active theme. An empty field keeps the theme value shown in grey.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => $icon,
                'owner' => 'm4p_core',
                'fields' => $fields[$id],
            ];
        }

        return $sections;
    }

    /**
     * Custom properties of the first `:root` block of the theme's tokens file, in
     * file order. The `-rgb` twins of colors are left out: they follow their color.
     *
     * @return array<string, string> name without dashes => value
     */
    public static function tokens(): array
    {
        return array_filter(
            self::parse(),
            static function (string $name): bool {
                return substr($name, -4) !== '-rgb';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return string absolute path, empty when the active theme has no such file
     */
    public static function tokensFile(): string
    {
        $relative = (string) M4pCoreSettings::get(self::TOKENS_PATH_KEY, self::DEFAULT_TOKENS_PATH);
        if (strpos($relative, '..') !== false) {
            return '';
        }

        $file = _PS_THEME_DIR_ . ltrim($relative, '/');

        return is_file($file) ? $file : '';
    }

    /**
     * Builds the CSS printed in the page head from the stored values and keeps it
     * in one row, so a page view reads a single value.
     */
    public static function compile(): void
    {
        $all = self::parse();
        $tokens = self::tokens();
        $lines = [];

        foreach ($tokens as $name => $default) {
            $value = (string) M4pCoreSettings::get(self::keyOf($name), '');
            if ($value === '' || in_array($name, self::FONT_TOKENS, true)) {
                continue;
            }
            $lines[] = '--' . $name . ':' . $value;

            $rgb = self::hexToRgb($value);
            if ($rgb !== null && isset($all[$name . '-rgb'])) {
                $lines[] = '--' . $name . '-rgb:' . $rgb;
            }
        }

        M4pCoreSettings::set(self::CSS_KEY, $lines === [] ? '' : ':root{' . implode(';', $lines) . '}');
    }

    /**
     * @return string the `<style>` tag for the page head
     */
    public static function head(): string
    {
        $css = (string) Configuration::get(M4pCoreSettings::PREFIX . self::CSS_KEY);

        return $css === '' ? '' : '<style id="m4p-core-theme">' . str_replace('</', '<\/', $css) . '</style>';
    }

    /**
     * @param string $name token name without dashes
     *
     * @return string settings key, such as THEME_ACCENT_HOVER
     */
    public static function keyOf(string $name): string
    {
        return self::KEY_PREFIX . strtoupper(str_replace('-', '_', $name));
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isColor(string $value): bool
    {
        return (bool) preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)
            || (bool) preg_match('/^(rgb|rgba|hsl|hsla)\([0-9.,%\s\/a-z()-]+\)$/i', $value);
    }

    /**
     * @return array<string, string> every token of the first `:root` block, the `-rgb` ones included
     */
    private static function parse(): array
    {
        $file = self::tokensFile();
        if ($file === '') {
            return [];
        }

        $css = (string) preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents($file));
        if (!preg_match('~:root\s*\{([^}]*)\}~', $css, $root)) {
            return [];
        }
        preg_match_all('~--([a-z0-9-]+)\s*:\s*([^;]+);~i', $root[1], $matches, PREG_SET_ORDER);

        $out = [];
        foreach ($matches as [, $name, $value]) {
            $out[$name] = trim((string) preg_replace('/\s+/', ' ', $value));
        }

        return $out;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    private static function groupOf(string $name, string $default): string
    {
        if (strpos($name, 'font-') === 0) {
            return 'theme_typography';
        }
        if (self::isColor($default)) {
            return 'theme_colors';
        }
        if (preg_match('/^(fs|fw|lh|ls|text)-/', $name)) {
            return 'theme_typography';
        }
        if (preg_match('/^(spacing|container|gutter|section|control|tap)/', $name)) {
            return 'theme_layout';
        }

        return 'theme_shape';
    }

    /**
     * @param string $value
     *
     * @return string|null "r, g, b" for a hex color
     */
    private static function hexToRgb(string $value): ?string
    {
        if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value, $m)) {
            return null;
        }
        $hex = strlen($m[1]) === 3 ? preg_replace('/(.)/', '$1$1', $m[1]) : $m[1];

        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    }
}
