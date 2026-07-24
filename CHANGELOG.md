# Changelog

All notable changes to `filament-column-filters` will be documented in this file.

## 0.0.2 - 2026-07-24

- Theming: every colour is exposed as an `--fcf-*` CSS variable, overridable on `:root` and `.dark`. Most derive from `--fcf-accent`, which defaults to the panel's primary colour.
- The column header icon is lighter when idle, and dark mode no longer inherits the shade picked for a white background.

## 0.0.1 - 2026-07-24

- Initial release: Excel-style column header filters (search, date range with quick presets, single/multi select) with syncing to regular Filament table filters. English + Hebrew translations, RTL support.
