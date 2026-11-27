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
 * Gives every body cell of a CMS table the label of its column (`data-label` from the `<th>` of `<thead>`),
 * so a theme can show a row as a label–value list on narrow screens.
 */
class M4pCoreCmsTables
{
    public static function label(string $html): string
    {
        if (stripos($html, '<thead') === false) {
            return $html;
        }

        return (string) preg_replace_callback('#<table\b.*?</table>#is', static function (array $table): string {
            if (!preg_match('#<thead\b.*?</thead>#is', $table[0], $head)) {
                return $table[0];
            }
            preg_match_all('#<th\b[^>]*>(.*?)</th>#is', $head[0], $cells);
            $labels = array_map(static function (string $cell): string {
                return trim(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }, $cells[1]);

            return (string) preg_replace_callback('#<tr\b[^>]*>.*?</tr>#is', static function (array $row) use ($labels): string {
                $column = 0;

                return (string) preg_replace_callback('#<td\b([^>]*)>#i', static function (array $cell) use ($labels, &$column): string {
                    $label = $labels[$column++] ?? '';
                    if ($label === '' || stripos($cell[1], 'data-label') !== false) {
                        return $cell[0];
                    }

                    return '<td' . $cell[1] . ' data-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
                }, $row[0]);
            }, $table[0]);
        }, $html);
    }
}
