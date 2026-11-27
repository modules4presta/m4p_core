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
 * Table of contents of a CMS page built from its H2 headings ($cms_toc). The page layout chosen in the
 * page form forces it ("document") or turns it off ("page"); otherwise two headings are needed.
 */
class M4pCoreCmsToc
{
    /**
     * Runs while the CMS controller builds its template variables, after the filterCmsContent filters,
     * so the headings are the ones the page shows.
     */
    public static function assign(Context $context): void
    {
        $cms = $context->smarty->getTemplateVars('cms');
        if (!is_array($cms) || empty($cms['content'])) {
            return;
        }
        $cms['content'] = M4pCoreCmsTables::label((string) $cms['content']);
        $context->smarty->assign('cms', $cms);
        $layout = $cms['layout'] ?? 'auto';
        if ($layout === 'page') {
            return;
        }

        [$content, $toc] = self::build((string) $cms['content']);
        if (count($toc) < ($layout === 'document' ? 1 : 2)) {
            return;
        }

        $cms['content'] = $content;
        $context->smarty->assign([
            'cms' => $cms,
            'cms_toc' => $toc,
        ]);
    }

    /**
     * Gives every H2 an id (an existing one is kept) and lists the headings.
     *
     * @return array{0: string, 1: array<int, array{id: string, number: string, label: string}>}
     */
    public static function build(string $content): array
    {
        $toc = [];
        $used = [];

        $content = preg_replace_callback(
            '#<h2\b([^>]*)>(.*?)</h2>#is',
            function (array $m) use (&$toc, &$used): string {
                $attrs = $m[1];
                $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($text === '') {
                    return $m[0];
                }

                if (preg_match('/\bid\s*=\s*"([^"]+)"/i', $attrs, $idMatch)) {
                    $id = $idMatch[1];
                } else {
                    $base = Tools::str2url(preg_replace('/^§?\s*\d+\.\s*/u', '', $text)) ?: 'sekcja';
                    $id = $base;
                    for ($i = 2; isset($used[$id]); ++$i) {
                        $id = $base . '-' . $i;
                    }
                    $attrs .= ' id="' . $id . '"';
                }
                $used[$id] = true;

                $number = '';
                $label = $text;
                if (preg_match('/^(§?\s*\d+\.)\s*(.+)$/u', $text, $parts)) {
                    $number = $parts[1];
                    $label = $parts[2];
                }
                $toc[] = ['id' => $id, 'number' => $number, 'label' => $label];
                $inner = $number === '' ? $m[2] : (string) preg_replace(
                    '/^(\s*)(' . preg_quote($number, '/') . ')/u',
                    '$1<span class="m4p-cms-number">$2</span>',
                    $m[2],
                    1
                );

                return '<h2' . $attrs . '>' . $inner . '</h2>';
            },
            $content
        );

        return [$content, $toc];
    }
}
