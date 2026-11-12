# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

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
  context instead of the multistore scope in use.
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
