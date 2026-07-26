# Changelog

All notable changes to `filament-column-filters` will be documented in this file.

## 0.0.4 - 2026-07-26

- Dark mode: the header filter icon no longer stays at the white-background accent shade when it is active or its panel is open — the highlighted states and the active dot step up to `--primary-400` / `--primary-300`.

## 0.0.3 - 2026-07-24

- The header filter icon is now highlighted while its popup is open, so the open column is obvious. Themeable via `--fcf-trigger-color-open` / `--fcf-trigger-bg-open`.

## 0.0.2 - 2026-07-24

- Theming: every colour is exposed as an `--fcf-*` CSS variable, overridable on `:root` and `.dark`. Most derive from `--fcf-accent`, which defaults to the panel's primary colour.
- The column header icon is lighter when idle, and dark mode no longer inherits the shade picked for a white background.

## 0.0.1 - 2026-07-24

- Initial release: Excel-style column header filters (search, date range with quick presets, single/multi select) with syncing to regular Filament table filters. English + Hebrew translations, RTL support.
