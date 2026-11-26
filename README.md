# CT Multi Sticky Sidebar

**Sticky sidebars for WordPress that cost almost nothing.** Any number of sidebars per page, one small static JS file per site, no jQuery, no extra database queries on the frontend, and nothing at all on mobile devices or on pages that have no sidebar.

It uses the scrolling engine of [Theia Sticky Sidebar](https://github.com/WeCodePixels/theia-sticky-sidebar) v2 (MIT), the same "modern" behavior many sites already rely on, wrapped in a WordPress plugin designed around one goal: **do not slow the site down.**

- License: GPL-2.0-or-later (the bundled Theia engine is MIT, see [Credits](#credits))
- Requires WordPress 6.3+ and PHP 7.4+
- Current version: 3.1.0 ([changelog](CHANGELOG.md))
- Translations: English (source), Vietnamese

---

## Why another sticky sidebar plugin?

A sticky sidebar is a small visual effect. It should not cost a site more than the effect itself is worth. The popular plugins do:

| | Theia Sticky Sidebar (WordPress plugin 1.8.0) | My Sticky Sidebar 1.4.1 | **CT Multi Sticky Sidebar** |
|---|---|---|---|
| Sidebars per page | Several | **One** sidebar/content pair | **Any number** of groups, each with its own container and margins |
| jQuery | Required | Required | **Not needed** |
| JS requests (plugin files) | 3: `ResizeSensor.js`, `theia-sticky-sidebar.js`, `main.js` | 2: `detectmobilebrowser.js`, `theia-sticky-sidebar.js` | **1** generated file |
| Size of the plugin's JS (gzip) | ≈ 5.4 KB **+ jQuery & jQuery Migrate ≈ 35 KB** | ≈ 5.3 KB **+ jQuery & jQuery Migrate ≈ 35 KB** | **≈ 4.9 KB, nothing else** |
| Settings on every page view | Read from an autoloaded option and printed into the HTML (`wp_footer` data attributes) | Read from an autoloaded option and printed inline (`wp_localize_script`) | **Not read, not printed**: baked into the static JS file |
| Mobile | Loads everything on mobile too | Disabling mobile is done **in JavaScript**, so phones still download jQuery and both files | **Nothing is sent to mobile**: the decision is made on the server |
| Pages without a sidebar | Loads on every page where sidebars are enabled | Loads on every page that is not excluded | **Optional**: load only when a watched widget area is actually rendered |
| Resize detection | `ResizeSensor` (injects hidden elements and listens to their scroll events) | jQuery-based engine v1 | Native `ResizeObserver`, batched with `requestAnimationFrame` |
| Maintenance | No longer updated | Updated, but the sticky engine inside is the old jQuery-based v1 | Maintained; engine updated to Theia v2 |

Numbers measured in November 2026 on a production Multisite running this plugin with two sidebar groups. The generated file is 19 KB raw / 4.9 KB gzip. jQuery 3.7.1 + Migrate 3.4.1 shipped with WordPress weigh 101 KB raw / 35 KB gzip. The old plugins' sizes are those of the JS files they enqueue on the frontend.

> If another plugin on a page already needs jQuery, the jQuery saving on that page is zero. The point is that **this plugin never forces jQuery onto a page**, so once your other plugins are jQuery-free, your pages are too.

---

## How it keeps the site fast

### 1. One static, cacheable JS file per site
When you save the settings, the plugin **generates a single file**:

```
wp-content/uploads/lite-sticky-sidebar/lss-1.js               (main site)
wp-content/uploads/sites/2/lite-sticky-sidebar/lss-2.js       (Multisite, site 2)
```

The file contains the Theia v2 engine, your settings (as JSON) and a few lines of init code. The browser makes **one request**, and that request is a plain static file: it can be served by the web server, a CDN or the browser cache without touching PHP.

- The URL carries `?ver=<file modification time>`, so every new version busts every cache automatically. If two saves happen in the same second, the plugin bumps the timestamp by one second so the URL still changes.
- The file is written to a temporary name and then renamed, so a visitor can never download a half-written file.
- If the file disappears (site cloned, uploads cleaned), it is rebuilt automatically on the next page view. A failed rebuild is retried at most every 10 minutes, never on every request.

### 2. Zero extra database queries on the frontend
The settings are split into two options on purpose:

| Option | Autoload | Content | Read by |
|---|---|---|---|
| `lss_settings` | **no** | Everything: selectors, margins, behavior... (JSON) | wp-admin and the file generator only |
| `lss_runtime` | yes | Only what the frontend needs to decide *whether* to load the file: the watched widget areas and the excluded page types (a few hundred bytes) | `wp_enqueue_scripts` |

WordPress already loads all autoloaded options in **one query** at the start of every request (or from the object cache). Keeping the frontend's needs in that tiny autoloaded option means **no additional query**. Keeping the full settings out of autoload means the larger value **never inflates** the autoloaded data that every page view pays for. The full settings are never needed on the frontend anyway: they are already inside the JS file.

### 3. Nothing on mobile, decided on the server
Sticky sidebars make no sense on phones: the sidebar sits below the content. Other plugins still send their JavaScript (and jQuery) to phones and switch the effect off *in the browser*.

CT Multi Sticky Sidebar checks `wp_is_mobile()` in PHP and **does not enqueue anything**. Phones download zero bytes and run zero JavaScript for this feature. The behavior can be changed with the `lss_is_mobile` filter, for example to allow tablets.

> **Page cache:** because the decision is made on the server, a page cache must keep separate copies for mobile and desktop (WP Rocket, LiteSpeed Cache, W3 Total Cache and others have this option). Without it, a page cached for a phone could be served to a desktop visitor without the sticky effect, or the other way round.

### 4. Loaded only where a sidebar is actually rendered
Tick the widget areas your sidebars use, and the script is enqueued **only when the template really calls `dynamic_sidebar()` for one of them and the area has widgets** (hook `dynamic_sidebar_before`). Pages with a full-width layout, landing pages, or templates without that area do not load the file.

- No DOM scanning, no output buffering, no guessing from CSS selectors: WordPress itself says whether the sidebar is printed.
- The `<script>` tag is printed in the footer, because the decision happens while the page is being rendered.
- No area ticked = load on every page (simple setups).
- You can also exclude whole page types: home/blog page, category archives, pages, single posts.

### 5. A modern engine without jQuery
- **Theia Sticky Sidebar v2.0.0**, hand-ported from the upstream TypeScript source to plain JavaScript (`window.TheiaStickySidebar`). No build step, no dependency.
- Size changes are detected with the browser's native **`ResizeObserver`** instead of the old `ResizeSensor`, which injected hidden elements into every sidebar.
- One patch on top of upstream: the observer's callback runs through `requestAnimationFrame`. It coalesces bursts of size changes into one update per frame and avoids the "ResizeObserver loop completed with undelivered notifications" console error. The patch is marked `LSS PATCH` in the source.
- The "modern" behavior is kept: a sidebar taller than the screen sticks to the **bottom** while you scroll down and to the **top** while you scroll up, so every widget in it can be read.

### 6. No inline code, safe settings
- No inline `<script>` or settings in the HTML. Only one `<script src>` in the footer, which also works well with a strict Content Security Policy.
- Selectors are filtered to the characters a CSS selector can contain, at most 500 characters, at most 20 groups; numbers are clamped to sane ranges. Settings reach the JS file only through `wp_json_encode()`, so they cannot break out of the JSON.
- Each group is initialized in its own `try/catch`: one invalid selector cannot break the other sidebars.
- Settings and regeneration require `manage_options` and a nonce.

---

## Features

- **Multiple sidebar groups per page.** Each group has its own sidebar selector(s), container and optional top/bottom margins.
- Global settings: minimum viewport width, top/bottom margin, behavior (`modern`, `stick-to-top`, `stick-to-bottom`), update sidebar height, disable on responsive layouts.
- Load only where selected widget areas are rendered; exclude page types.
- **Multisite ready.** Activate it per site (not network-wide); every site gets its own settings and its own file in its own uploads folder.
- Clean uninstall: deleting the plugin removes its options and generated files on every site.
- Translatable (`ct-multi-sticky-sidebar` text domain, POT included); Vietnamese translation bundled.

---

## Installation

1. Download the zip from the [releases](https://github.com/congtoan/ct-multi-sticky-sidebar/releases), then go to **Plugins → Add New → Upload Plugin**. You can also copy the `ct-multi-sticky-sidebar` folder to `wp-content/plugins/`.
2. Activate it. On Multisite, activate it on each site that needs it.
3. Open **Settings → CT Multi Sticky Sidebar**.
4. Set the sidebar selector of your theme. The default `#secondary, #right-sidebar, #left-sidebar` already covers the WordPress default themes, GeneratePress, Astra, Kadence, OceanWP and many others. Leave the container empty to use the sidebar's parent element.
5. Optionally tick the widget areas your sidebars use and the page types to exclude.
6. Click **Save and regenerate the JS file**, then clear your page cache.

### Finding the right selectors
Open a page in your browser, right-click the sidebar, choose **Inspect**, and look for the element that wraps all widgets. Typical ids are `#secondary`, `#sidebar` or `#right-sidebar`. The container is the element that holds both the content and the sidebar, such as `#content`, `.site-content` or `#main`.

---

## Developer reference

### Filters
| Filter | Arguments | Use |
|---|---|---|
| `lss_is_mobile` | `bool $is_mobile` | Return `false` to load on devices that `wp_is_mobile()` reports as mobile (e.g. tablets). |
| `lss_watch_areas` | `array $area_ids` | Change the watched widget areas in code. Empty array = always load. |
| `lss_config` | `array $settings, int $blog_id` | Change the sticky settings in code. Applied **when the file is generated**, so regenerate after changing it. |

### Upgrading the engine
Replace `assets/theia-sticky-sidebar.js`, then click **Regenerate the JS file** on each site.

### Files
| File | Role |
|---|---|
| `ct-multi-sticky-sidebar.php` | Bootstrap, constants, text domain, activation |
| `includes/functions.php` | Defaults, sanitization, settings storage, file generator |
| `includes/frontend.php` | Conditional enqueue (mobile, page types, widget areas) |
| `includes/admin.php` | Settings page, save and regenerate handlers |
| `assets/theia-sticky-sidebar.js` | Theia Sticky Sidebar v2.0.0 engine, plain JS |
| `assets/init.js` | Init template; the settings replace `/*__LSS_CONFIG__*/{}` |
| `languages/` | POT and translations |
| `tools/po2mo.py` | Compile `.po` to `.mo` with plain Python |

The internal prefix `lss_` (functions, options, filters) and the uploads folder `lite-sticky-sidebar` come from the plugin's first name, "Lite Sticky Sidebar". They are kept unchanged so existing installs keep their settings and files.

### Translating
Copy `languages/ct-multi-sticky-sidebar.pot` to `ct-multi-sticky-sidebar-{locale}.po`, translate it, then run:
```
python tools/po2mo.py languages/ct-multi-sticky-sidebar-{locale}.po
```
Put the `.mo` in the plugin's `languages/` folder or in `wp-content/languages/plugins/` (that one survives updates). Pull requests with translations are welcome.

---

## Known limitations

- **Mobile detection is server-side.** Tablets count as mobile for `wp_is_mobile()`, so a tablet in landscape gets no sticky sidebar unless you use the `lss_is_mobile` filter. A page cache must separate mobile and desktop copies (see above).
- **Modern browsers only.** The engine needs `ResizeObserver` (all current browsers; not Internet Explorer). In older browsers the sidebar simply does not stick, and nothing breaks.
- Selectors containing escaped characters (`\`) or non-ASCII characters are stripped by the sanitizer.
- Changing settings through the `lss_config` filter takes effect only after the file is regenerated.

---

## Credits

- **Theia Sticky Sidebar** by WeCodePixels: the scrolling engine, version 2.0.0, MIT license. <https://github.com/WeCodePixels/theia-sticky-sidebar>. The full license text is in [`assets/LICENSE-theia-sticky-sidebar.txt`](assets/LICENSE-theia-sticky-sidebar.txt) and its copyright notice stays at the top of the engine file and of every generated file.
- **CT Multi Sticky Sidebar** by Cong Toan: WordPress plugin, settings, file generation, conditional loading, JavaScript port of the engine.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE). The bundled Theia Sticky Sidebar engine remains under the MIT license.
