# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [1.25.0] - 2026-11-27

### Added

- Section "Sales model": the shop sells to companies (B2B), consumers (B2C, default on a new install) or
  both (`SHOP_MODE`), and the customer groups that get the company view when it sells to both
  (`B2B_GROUPS`). `M4pCoreShopMode::current()` gives the view of the current visitor; a module can replace
  the model for one visitor on the `actionM4pCoreShopMode` hook.
- `Group::getPriceDisplayMethod()` override: in the consumer view the storefront shows gross prices to every
  customer group; the back office keeps the group setting.
- Company data: court register number, registry court, share capital, veterinary approval number and the
  privacy policy page linked in the checkout.
- Section "Sales department": phone, hours per weekday and e-mail (`SALES_PHONE`, `SALES_HOURS`,
  `SALES_EMAIL`), read by the header strip, the footer and m4p_contactwidget; empty fields fall back to
  m4p_contactwidget and the shop contact details.
- Section "Footer": light logo for dark backgrounds, payment methods, delivery and pickup.
- Section "Newsletter": the CMS page with the sending and unsubscribing rules, the title, the text and the
  photo next to the sign-up form (`$m4p_newsletter`).
- Section "Delivery": order cut-off (default 14:00) and delivery days (default Monday to Friday), one value for
  the header strip, the mini cart and m4p_homeintro.
- Section "Header": strip above the header with its texts for visitors and for the consumer view (`{cutoff}`
  placeholder), low header after scrolling on phones, dimmed page and recommended product in the category
  panel, subcategory tiles on the category page (0–8, default 4), the product feature with the pack size and
  the logo sign for phones (`$m4p_logo_mark`, stored under `img/m4p_logo/`).
- Section "Home page: Featured": label, title, description and link of the text tile that opens the Featured
  row (`$m4p_featured_tile`).
- Section "Cart": after adding a product the mini cart opens (default) or a window with a summary.
- Section "Windows": animation of the theme's windows (on or off, fade, grow or slide, opening time), printed as
  the `--modal-*` custom properties.
- Section "Product page": the features with the origin and the sale unit, up to three facts with an icon and
  the text visitors see instead of the price.
- Section "Product page: content cards": the features with the ingredients, the allergens, "Free from" and the
  nutrition basis, the rows of the nutrition table and its indented "of which" rows. The upgrade picks the
  features by their usual names.
- Section "Payment and delivery methods": an icon for every carrier and payment module (automatic from the
  carrier name or the module when not chosen), and the name and the line under it of every payment method in
  the checkout.
- Section "Customer account: orders": the statuses of the "To pay" and "In progress" filters (automatic from
  the status settings when not chosen) and the orders per page (default 20).
- Section "Product images": the largest file (default 200 KB, 0 turns it off) and the longest side of the
  original (default 1440 px). After an upload, an import or a thumbnail regeneration (`actionWatermark`) the
  original becomes a JPEG within the longest side and every file above the limit is compressed again until it
  fits; files still above it are written to the log.
- Section "Browser tab": the title shown while the customer is on another tab (`window.m4pTabBlurTitle`).
- Field types `time`, `days` and `week` for sections; a `days` field marked `optional` may stay empty.
- Template variables for the M4P theme on `actionFrontControllerSetVariables`, also for the AJAX refresh of the
  mini cart: `$m4p_shop`, `$m4p_header`, `$m4p_footer`, `$priceDisplay`, `$m4p_product`, `$m4p_category`,
  `$m4p_listing`, `$m4p_bestsellers` (search and 404 pages), `$m4p_cart` (cart and checkout), `$m4p_methods`,
  `$m4p_order` and `$m4p_order_transfer` (order confirmation, order page, guest tracking), `$m4p_account`,
  `$m4p_history`, `$m4p_order_tones`, `$m4p_wishlists`, `$m4p_discounts` and `$m4p_slips` (customer account).
- `displayM4pPrice` hook: the second price line of a product or cart line,
  `{hook h='displayM4pPrice' product=$product tax=true}`.
- Template modifiers `{$quantity|m4p_units:$unit}` (Polish plural forms of sale units, with `:'from'` for the
  genitive), `{$name|m4p_nbsp}` (no-break space between a number and its unit) and `{$amount|m4p_price}`.
- Front controllers `checkoutaddress` (moves the cart onto a saved address picked in the one-page checkout) and
  `wishlistquantity` (sets the quantity of a product on a shopping list of blockwishlist); both need the
  front-office token and a signed-in customer.
- One-page checkout: the delivery country chosen in the address form sets the context country until the cart
  has an address, so carriers, shipping costs and payment methods follow it.
- Table of contents of a CMS page (`$cms_toc`) from its H2 headings, each heading given an id; the layout
  "document" shows it, "page" never, "auto" from two headings. The number that opens a heading sits in
  `<span class="m4p-cms-number">`, and every body cell of a table with `<thead>` gets the label of its column
  in `data-label`.
- `M4pCoreCategoryImage::ensure()`: makes a missing size of a category image or thumbnail on its first view, so
  a theme enabled after the upload needs no thumbnail regeneration for categories.

### Changed

- The upgrade stores "Companies (B2B)" as the sales model of a shop that has no value yet, so an existing shop
  keeps the view it shows, and fills the new sections from m4p_homeintro, m4p_themesettings and
  m4p_contactwidget where those hold a value.
- The theme sections leave out the tokens owned by the "Windows" section.

## [1.4.0] - 2026-10-28

### Changed

- The theme sections hold every custom property of the theme's tokens file except the `--font-*`
  ones, which are left to the theme.
- The compiled theme CSS is one `:root` rule, kept in a single stored value and printed in the page
  head.

### Removed

- Typeface handling: the "Theme: fonts" section, the download from Google Fonts and the font files
  served from `img/m4p_core/fonts/`. The `M4P_CORE_FONT_*` values stay in the database, so a module
  that takes the typefaces over finds them where they were.

## [1.3.0] - 2026-10-27

### Changed

- The layout of a CMS page is stored per shop, like the date of its last change, so a page shared
  by several shops can be laid out differently in each.
- The date of the last change of a CMS page is written by the database clock.
- `M4pCoreSettings::set()` takes the shop to write to, and falls back to the shop of the current
  context.
- Both back-office pages name the shop the way the multistore selector does, and the shop name from
  the front office when the installation has one shop.

## [1.2.0] - 2026-10-21

### Added

- Layout choice in the CMS page form: automatic, document with a table of contents, or a regular
  page; available to the theme as `layout`.

## [1.1.0] - 2026-10-16

### Added

- Theme sections built from the active theme's tokens file: every CSS custom property becomes a
  field, printed in the page head when filled.
- Heading and body fonts from Google Fonts or an uploaded file, both served from the shop.
- Date of the last content change of each CMS page, available to the theme as `date_upd`.
- Field types `color`, `css` and `file` for sections added by other modules.

## [1.0.0] - 2026-10-16

### Added

- Shared settings for the Modules4Presta modules, stored per shop as `M4P_CORE_*` rows in
  `configuration`.
- Two back-office pages under one menu entry: an overview and the settings themselves, one section
  at a time.
- Built-in sections: company data, measurement, addresses.
- `actionM4pCoreSections`, the hook another module uses to add its own section; sections and fields
  are validated before they are rendered.
- `M4P_CORE_SCHEMA`, the version of the settings a reading module can compare against.
- Validation per field type for e-mail, address, phone, number, choice and text.
- An action that adds the pages back on a shop whose database predates the module.
- English and Polish catalogues.
