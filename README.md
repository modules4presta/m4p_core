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
pages, and the other modules read them. It also edits the theme's design tokens, adds two fields
to CMS pages (the date of the last change and the page layout) and prepares the template variables
the M4P theme reads on the storefront: header, footer, product page, cart, checkout and customer account.

Nothing here is required. A module reads this module only if it is installed and enabled; without
it, it falls back to its own value, key by key.

### Key features

- **Shared sections** – company data, measurement identifiers and addresses, each key stored as `M4P_CORE_<FIELD>`
- **Theme tokens as fields** – the CSS custom properties of the active theme, with a colour picker for colours and the theme value shown next to each field
- **Sections from other modules** – a module adds its own section on the `actionM4pCoreSections` hook
- **CMS page date** – a date of the last real change of each CMS page, for the theme to print
- **CMS page layout** – automatic, document with a table of contents, or a regular page
- **Sales model** – companies (B2B), consumers (B2C) or both, with the customer groups that get the company view
- **Theme variables** – ready data for the header, footer, product page, category page, cart, order confirmation and customer account pages
- **Product image limit** – the original and every thumbnail fitted in a file size after each upload and thumbnail regeneration
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
| Sales model | the shop sells to companies (B2B), consumers (B2C, default) or both; the customer groups with the company view |
| Company data | legal name, address, tax number, court register number, registry court, share capital, veterinary approval number, phone, e-mail, opening hours, privacy policy page |
| Sales department | phone, weekly hours, e-mail – shown in the header strip, the footer and m4p_contactwidget |
| Footer | light logo, payment methods, delivery, pickup |
| Measurement | Google Tag Manager container, report events, `utm_source` for outbound links |
| Addresses | module shop, support page |
| Newsletter | page with the sending and unsubscribing rules, title, text and photo next to the sign-up form |
| Delivery | order cut-off, delivery days |
| Header | strip above the header and its texts for visitors and for the consumer view, low header after scrolling, dimmed page and recommended product in the category panel, subcategory tiles on the category page, pack-size feature, logo sign for phones |
| Home page: Featured | label, title, description and link of the text tile that opens the Featured row |
| Cart | after adding to the cart: open the mini cart or a window with a summary |
| Windows | animation of the theme's windows: on or off, fade, grow or slide, opening time |
| Product page | features with the origin and the sale unit, up to three facts with an icon, text for visitors instead of the price |
| Product page: content cards | features with the ingredients, the allergens, "Free from", the nutrition basis and the nutrition table rows (with the indented ones); every other feature not shown at the top goes to Logistics |
| Payment and delivery methods | the icon of every carrier and payment method in the checkout and on the order confirmation (automatic choice from the name or the module), and the name and the line under it of every payment method in the checkout |
| Customer account: orders | the statuses of the "To pay" and "In progress" filters, orders per page |
| Product images | largest file in KB (default 200), longest side of the original (default 1440 px) – applied after every upload and thumbnail regeneration; 0 KB turns the limit off |
| Browser tab | title shown while the customer is on another tab |
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

The module also builds the table of contents from the page's H2 headings, after the `filterCmsContent`
filters: each heading gets an id (an existing one is kept) and the theme gets `$cms_toc` – a list of
`id`, `number` (a leading "1." or "§ 1.") and `label`. "document" shows it with one heading, "auto" from
two headings, "page" never.

## Theme variables

On `actionFrontControllerSetVariables` the module assigns the data the M4P theme prints. A theme that does
not read them ignores them.

| Where | Variables |
|---|---|
| Every front page and the AJAX refresh of the mini cart | `$m4p_shop` (sales model, whether prices show), `$m4p_header` (header strip, delivery day and cut-off, sales phone and hours, account, mini cart amounts), `$m4p_footer` (light logo, sales department, payments, delivery, company line), `$m4p_newsletter`, `$m4p_logo_mark`, `$priceDisplay` |
| Home page | `$m4p_featured_tile` |
| Product page | `$m4p_product` (origin, sale unit, facts, composition, nutrition, price of one pack sold by weight) |
| Category page and product lists | `$m4p_category` (the cover as a header photo when it is at least 1.6 times wider than tall, subcategory icons and tiles, product counts), `$m4p_listing` (products per page) |
| Search and 404 pages | `$m4p_bestsellers` (the most ordered products) |
| Cart and checkout | `$m4p_cart` (sale units of the lines, VAT amount), `$m4p_methods` (carrier and payment icons, payment names) |
| Order confirmation, order page, guest tracking | `$m4p_order` (lines, amounts, steps, carrier, address, sales manager), `$m4p_order_transfer` (bank details while a transfer is to be paid) |
| Customer account | `$m4p_account` (dashboard), `$m4p_history` (order list with filters), `$m4p_order_tones`, `$m4p_wishlists` (blockwishlist lists), `$m4p_discounts`, `$m4p_slips` |

Templates also get the modifiers `{$quantity|m4p_units:$unit}` (Polish plural forms of sale units),
`{$name|m4p_nbsp}` (no-break space before a unit) and `{$amount|m4p_price}`, and the `displayM4pPrice` hook
prints the second price line of a product or cart line: `{hook h='displayM4pPrice' product=$product tax=true}`.

Two front controllers serve the theme: `checkoutaddress` moves the cart of the signed-in customer onto a saved
address picked in the one-page checkout, and `wishlistquantity` sets the quantity of a product on the customer's
shopping list. Both answer JSON, need the front-office token and act only on the addresses and lists of the
signed-in customer. In the one-page checkout the delivery country typed in
the address form sets the context country until the cart has an address, so carriers and payment methods follow it.

## Adding a section from another module

A module adds its own section on the `actionM4pCoreSections` hook. The section is validated before
it is rendered: it needs a title and at least one field, and a field needs a key and a known type
(`text`, `textarea`, `email`, `tel`, `url`, `number`, `switch`, `select`, `color`, `css`, `file`,
`time`, `days`, `week`). A `select` and a `days` field take `options` (value => label); `days` stores the ticked
values comma-separated, for example `1,2,3,4,5`, and may stay empty when the field is marked `optional`.
`week` stores one time range per weekday.

```php
public function hookActionM4pCoreSections(array $params): void
{
    $params['sections']['pickup'] = [
        'title' => $this->trans('Pickup', [], 'Modules.Mymodule.Admin'),
        'description' => $this->trans('Used by the modules that print pickup times.', [], 'Modules.Mymodule.Admin'),
        'icon' => 'icon-truck',
        'owner' => $this->name,
        'fields' => [
            ['key' => 'PICKUP_FROM', 'label' => 'Pickup from', 'type' => 'time'],
            ['key' => 'PICKUP_SATURDAY', 'label' => 'Saturday pickup', 'type' => 'switch'],
        ],
    ];
}
```

The values are then stored as `M4P_CORE_PICKUP_FROM` and `M4P_CORE_PICKUP_SATURDAY`, and any
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
| Requirements | none; the product image limit needs GD with WebP support |
| Multistore | supported – values belong to the shop being edited; the pages ask for a single shop in the selector |
| Themes | any theme; the theme sections need a tokens file (`assets/css/custom/tokens.css` or the path in `M4P_CORE_THEME_TOKENS_PATH`), the theme variables are read by the M4P theme |

The module overrides `Group::getPriceDisplayMethod()`: in the consumer view of the sales model the storefront
shows gross prices whatever the customer group says; the back office keeps the group setting. It adds two tables,
for the date and the layout of CMS pages, and an **M4P** menu with two pages in the back office.

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
| **Sales model** | Who the shop sells to. Consumers (default) see gross prices without logging in; companies see net prices after logging in. With "Companies and consumers", customers in the chosen groups get the company view. |
| **Company data** | Legal name, address, tax and register numbers, phone, e-mail and opening hours as they should appear in documents, the footer and contact blocks, and the privacy policy page linked in the checkout. |
| **Sales department** | Phone, hours per weekday and e-mail; empty fields fall back to m4p_contactwidget and the shop contact details. |
| **Delivery** | Order cut-off (14:00) and delivery days (Monday to Friday) shown in the header strip, the mini cart and on the home page. |
| **Product images** | 200 KB and 1440 px suit most catalogues; keep the longest side at least the size of the largest image type, or set 0 KB to leave the files as uploaded. |
| **Measurement** | Google Tag Manager container, the switch for reporting events (on by default) and `utm_source` for outbound links. |
| **Addresses** | Links to the module shop and the support page. |
| **Newsletter** | The CMS page with the sending and unsubscribing rules (linked under the consent), the title, the text and the photo next to the form; empty fields give the theme's standard text. |
| **Header, footer, cart, windows, product page, payment and delivery methods, customer account** | What the M4P theme shows in those places; every field has a hint in the back office. |
| **Theme sections** | Values for the theme's tokens; an empty field keeps the theme value. |

## Frequently asked questions

**Do I need this module for the other modules to work?**
No. Every module of the family works on its own and reads this one only when it is installed and enabled.

**What happens to the values when I uninstall it?**
They stay in the database, so reinstalling the module brings them back.

**Can it break the theme?**
No. An empty field keeps the theme value, and a value that could end the declaration or load a file
(`;`, braces, `url(`, `@import`) is refused on save.

**Does it change prices?**
No. In the consumer view the storefront shows prices with tax; the amounts and the back office stay as they are.

**Does it change my product photos?**
Yes, when the Product images limit is above 0: after an upload or a thumbnail regeneration the original is saved
as a JPEG within the longest side and every file above the limit is compressed again. Set 0 KB to turn it off.

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
