# M4P Core for PrestaShop 1.7.6 – 9

**Company data, measurement identifiers, addresses and the look of the theme are typed once and read
by every module of the family, instead of being kept in five configuration pages that drift apart.**

> **Meta description (155 chars):** PrestaShop module holding the settings a family of modules shares: company data, measurement and addresses, with its own back-office pages. MIT.

---

## Why shared settings matter for your store

A shop that runs several modules from the same family ends up with the same phone number, the same
tax number and the same tracking identifier in five configuration pages. When the phone number
changes, four of them get updated and the fifth keeps showing the old one in the footer, the
contact widget or a document.

- **One place to edit** – a value changed here reaches every module that reads it
- **Per shop** – a multistore keeps one set of values for each shop
- **Nothing to lose** – modules keep working without this one, and uninstalling it leaves the values in place

## What the module does

The module keeps the settings a family of modules shares, once per shop, on its own back office
pages, and the other modules read them. It also edits the theme's design tokens and adds two fields
to CMS pages: the date of the last change and the page layout.

Nothing here is required. A module reads this module only if it is installed and enabled; without
it, it falls back to its own value, key by key.

### Key features

- **Shared sections** – company data, measurement identifiers and addresses, each key stored as `M4P_CORE_<FIELD>`
- **Theme tokens as fields** – the CSS custom properties of the active theme, with a colour picker for colours and the theme value shown next to each field
- **Sections from other modules** – a module adds its own section on the `actionM4pCoreSections` hook
- **CMS page date** – a date of the last real change of each CMS page, for the theme to print
- **CMS page layout** – automatic, document with a table of contents, or a regular page
- **Overview page** – how complete each section is and which modules of the family are installed

### When the module is uninstalled

Its back office pages and its hook go; the stored values and the two CMS tables stay, so putting the
module back brings everything along. Modules that read it fall back to their own configuration in
the meantime.

## Pages, not one long form

| Page | What is there |
|---|---|
| **M4P → Overview** | shop being edited, number of stored values, settings version, whether the hook is ready, how complete each section is, and which modules of the family are installed |
| **M4P → Shared settings** | the sections, one at a time: a list on the left, the fields of the chosen section on the right |

The module's own configuration page holds nothing but links to those two and a button that adds
them back if a restored database is missing them.

## Sections it ships

| Section | Fields |
|---|---|
| Company data | legal name, address, tax number, phone, e-mail, opening hours |
| Measurement | Google Tag Manager container, report events, `utm_source` for outbound links |
| Addresses | module shop, support page |
| Theme: colors, typography, layout and spacing, shape and effects | the CSS custom properties of the active theme, read from its tokens file |

Every value is one row in `configuration` named `M4P_CORE_<FIELD>`, stored for the shop being
edited, so a multistore keeps one set per shop. The back office prints the key under each field,
which is what another module reads.

## The look of the theme

The theme sections are built from the theme, not typed into this module. The module reads the first
`:root` block of `assets/css/custom/tokens.css` in the active theme and turns each custom property
into a field: a color gets a picker, anything else a text field. The theme value is shown in grey,
and an empty field keeps it. Another path can be stored under `M4P_CORE_THEME_TOKENS_PATH`,
relative to the theme folder. A theme without such a file shows no theme sections.

The `--font-display` and `--font-body` tokens are left out: the typefaces of a shop are the
theme's, declared in its tokens file. This module serves no font files.

Saving a theme section compiles the filled values into one `:root` rule, printed on
`displayHeader` after the theme's own styles. A color with an `-rgb` twin in the theme, such as
`--accent` and `--accent-rgb`, sets both. A value that could end the declaration or load a file
(`;`, braces, `url(`, `@import`) is refused.

## Date of the last change of a CMS page

PrestaShop keeps no date on CMS pages. The module stores one per page and shop, moved only when the
content of a save differs from the previous one, and adds it to the page as `date_upd` through
`filterCmsContent`. A theme prints it with `{$cms.date_upd}`; a page saved before the module was
installed gets the date of its first view.

## Layout of a CMS page

The CMS page form gets a "Page layout" field: automatic, document with a table of contents, or a
regular page. The choice reaches the theme as `{$cms.layout}` (`auto`, `document`, `page`); the
theme decides what each value looks like. A page without a stored choice is `auto`.

## Adding a section from another module

A module adds its own section on the `actionM4pCoreSections` hook. The section is validated before
it is rendered: it needs a title and at least one field, and a field needs a key and a known type
(`text`, `textarea`, `email`, `tel`, `url`, `number`, `switch`, `select`, `color`, `css`, `file`).

```php
public function hookActionM4pCoreSections(array $params): void
{
    $params['sections']['delivery'] = [
        'title' => $this->trans('Delivery', [], 'Modules.Mymodule.Admin'),
        'description' => $this->trans('Used by the modules that print delivery times.', [], 'Modules.Mymodule.Admin'),
        'icon' => 'icon-truck',
        'owner' => $this->name,
        'fields' => [
            ['key' => 'DELIVERY_CUTOFF', 'label' => 'Cut-off time', 'type' => 'text'],
            ['key' => 'DELIVERY_SATURDAY', 'label' => 'Saturday delivery', 'type' => 'switch'],
        ],
    ];
}
```

The values are then stored as `M4P_CORE_DELIVERY_CUTOFF` and `M4P_CORE_DELIVERY_SATURDAY`, and any
module can read them.

A section belongs to the module that adds it, so it shows only on the shops that module is
installed on. On a multistore where the module runs on one shop out of two, the other shop has no
such section and the values it may already hold stay in the database, out of reach of this page
until the module is installed there as well.

## Reading a value from another module

Read it without loading a single class from this module, so a shop without it behaves as if it
never existed:

```php
$phone = Configuration::get('M4P_CORE_COMPANY_PHONE');
```

A module that wants a fallback of its own keeps this shape: ask this module first, and when the key
is empty or the module is not enabled, use the value stored under the module's own name. The rule
that makes it predictable is per key, not all or nothing, so an older version of this module simply
carries fewer keys.

`M4P_CORE_SCHEMA` holds the version of the settings. A module that needs a given shape compares
against it before trusting the keys.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.4+ |
| Requirements | none |
| Multistore | supported – values belong to the shop being edited; the pages ask for a single shop in the selector |
| Themes | any theme; the theme sections need a tokens file (`assets/css/custom/tokens.css` or the path in `M4P_CORE_THEME_TOKENS_PATH`) |

The module performs no core overrides. It adds two tables, for the date and the layout of CMS pages,
and an **M4P** menu with two pages in the back office.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open **M4P → Shared settings**, pick a single shop in the multistore selector and fill the sections.
3. Check **M4P → Overview**: it shows how complete each section is and which modules read them.

If the M4P menu is missing after a database restore, the module configuration page has a button
that adds the pages back.

## Configuration options

The settings are the fields of the sections listed in [Sections it ships](#sections-it-ships).
Every field is optional; an empty one leaves the reading module with its own value.

| Setting | Description |
|---|---|
| **Company data** | Legal name, address, tax number, phone, e-mail and opening hours as they should appear in documents and contact blocks. |
| **Measurement** | Google Tag Manager container, the switch for reporting events (on by default) and `utm_source` for outbound links. |
| **Addresses** | Links to the module shop and the support page. |
| **Theme sections** | Values for the theme's tokens; an empty field keeps the theme value. |

## Frequently asked questions

**Do I need this module for the other modules to work?**
No. Every module of the family works on its own and reads this one only when it is installed and enabled.

**What happens to the values when I uninstall it?**
They stay in the database, so reinstalling the module brings them back.

**Can it break the theme?**
No. An empty field keeps the theme value, and a value that could end the declaration or load a file
(`;`, braces, `url(`, `@import`) is refused on save.

**Does it load fonts?**
No. Typefaces belong to the theme and the `--font-*` tokens are left out of the theme sections.

---

**Keywords:** prestashop shared settings, prestashop module configuration, prestashop company data, prestashop multistore settings, prestashop theme tokens, prestashop cms page date

## License

MIT – see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

## Contributing

Bug reports and pull requests are welcome – see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/oferta/moduly-prestashop) – we build and maintain PrestaShop stores.

© Nice Code sp. z o.o. (Modules4Presta) – released under the MIT license.
