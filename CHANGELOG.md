# Changelog

## [3.1.0] - 2026-11-26: First public release

### Changed
- Plugin renamed to **CT Multi Sticky Sidebar** (it was "Cong Toan Multi Sticky Sidebar"). The folder, text domain, option names, filters and uploads path are unchanged, so existing installs keep their settings and generated files.
- The admin screen, code comments and messages are now in English. Everything is translatable (text domain `ct-multi-sticky-sidebar`) and a POT file is included.
- Default settings for new installs are now generic: one group `#secondary, #right-sidebar, #left-sidebar` with an empty container (the sidebar's parent). This covers the WordPress default themes, GeneratePress, Astra, Kadence, OceanWP and others. Existing installs keep their saved groups.
- The "last generated" time on the settings page uses the site's date and time format.
- The admin help text explains the "modern" behavior and that an empty container means the parent element.

### Added
- Vietnamese translation (`languages/ct-multi-sticky-sidebar-vi.po/.mo`).
- `tools/po2mo.py`: compile `.po` to `.mo` with plain Python.
- `LICENSE` (GPL-2.0) and `assets/LICENSE-theia-sticky-sidebar.txt` (MIT, for the bundled engine).
- Plugin header: Author, Plugin URI, Text Domain, Domain Path, Update URI.

### Removed
- Internal design notes (`docs/handoff-v2.md`) from the repository.

## [3.0.0] - 2026-11-25: Theia v2 engine (no jQuery), load only where a sidebar is rendered

### Changed
- **Theia Sticky Sidebar v2.0.0** engine (MIT, plain JavaScript) replaces v1.7.0 (jQuery) and the ResizeSensor shim. Hand-ported from upstream `lib/theia-sticky-sidebar.ts` to `assets/theia-sticky-sidebar.js`.
  - One change from upstream: the `ResizeObserver` callback runs through `requestAnimationFrame` (marked `LSS PATCH`), which avoids "ResizeObserver loop" console errors.
  - The generated file no longer depends on jQuery.
- `assets/init.js` rewritten in plain JavaScript. It calls `new TheiaStickySidebar({...})` for each group, and wraps each group in `try/catch`.
- **Load only where a sidebar is rendered:** select widget areas in the settings. The script is then enqueued only when the template calls `dynamic_sidebar()` for a selected area that has widgets (hook `dynamic_sidebar_before`). With no area selected, the plugin always loads, like 2.x. Filter `lss_watch_areas`.
- **Do not load on:** home/blog page, category archives, pages, single posts.
- Option **`disableOnResponsiveLayouts`**.
- `areas` and `disableOn` are stored separately in the small autoloaded option `lss_runtime`, so the frontend reads them without an extra query. The full settings stay in `lss_settings` (not autoloaded).

### Fixed
- Default `updateSidebarHeight` is now `false` (2.0 defaulted to `true`, which differs from Theia's usual setup).
- Each generation uses a unique temp file name, so concurrent regenerations cannot clash.
- `?ver=` always changes when the content changes, even within the same second (mtime bumped by one second).
- An error is reported if `assets/init.js` lost its settings placeholder. Before, the file was generated without settings.
- Settings stored as an array (for example written with WP-CLI) are accepted. Settings saved by older versions get defaults for new keys.
- Uninstall also deletes `lss_runtime`.

### Tested
- 28 PHP tests with stubbed WordPress functions (run on a PHP 8.1 server) covering:
  - saving and loading settings;
  - file generation;
  - enqueue scenarios: with and without widgets, category pages, mobile, missing file.
- Browser tests on real production HTML with every original script removed, including jQuery:
  - all sidebar groups stick in "modern" mode;
  - each sidebar stops at the end of its container;
  - the effect switches off below 901 px.

### Upgrading from 2.x
Replace the plugin folder, then open **Settings** on **each site**:
1. Review the new options.
2. Tick the widget areas of your sidebars.
3. Click **Save and regenerate the JS file**.

Until you save, the plugin keeps loading the JS on every page, like 2.x.

## [2.0.0] - 2026-11-20: First version (before version control)
- Theia v1.7.0 engine (jQuery) + a `ResizeObserver`-based shim replacing ResizeSensor, combined into one file `uploads/(sites/N/)lite-sticky-sidebar/lss-{blog_id}.js`.
- Per-site settings page, settings stored as JSON with autoload = no.
- Nothing loaded on mobile (`wp_is_mobile()`).
