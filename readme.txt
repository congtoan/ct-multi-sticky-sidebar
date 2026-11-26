=== CT Multi Sticky Sidebar ===
Contributors: congtoan
Tags: sticky sidebar, sticky, sidebar, performance, no jquery
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 3.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sticky sidebars that cost almost nothing: any number per page, one static JS file, no jQuery, no frontend queries, nothing on mobile.

== Description ==

CT Multi Sticky Sidebar makes any number of sidebars sticky on the same page. Each sidebar group has its own container and margins. It uses the Theia Sticky Sidebar v2 engine (MIT) and its "modern" behavior: a tall sidebar sticks to the bottom while scrolling down and to the top while scrolling up.

It is built to keep the site fast:

* **No jQuery.** The engine is plain JavaScript.
* **One static JS file per site**, generated when you save the settings and served with `?ver=<mtime>`. It is cacheable by the browser and CDNs, and contains no inline script.
* **No extra database queries on the frontend.** The full settings are not autoloaded and are never read on the frontend. A tiny autoloaded option holds only what the frontend needs to decide whether to load the file.
* **Nothing on mobile.** The decision is made on the server with `wp_is_mobile()`, so phones download nothing.
* **Only where a sidebar is rendered** (optional). The file is loaded only when a selected widget area is actually printed by `dynamic_sidebar()`.
* Exclude page types: home, category archives, pages, posts.
* Multisite: activate per site; each site has its own settings and file.

Full documentation and comparison with other plugins: https://github.com/congtoan/ct-multi-sticky-sidebar

== Installation ==

1. Upload the `ct-multi-sticky-sidebar` folder to `/wp-content/plugins/` and activate it (on Multisite, per site).
2. Go to Settings > CT Multi Sticky Sidebar.
3. Set your sidebar and container selectors, optionally the widget areas and excluded page types.
4. Click "Save and regenerate the JS file" and clear your page cache.

If you use a page cache, enable separate caches for mobile and desktop.

== Changelog ==

= 3.1.0 =
* First public release: English admin screen, translatable (POT + Vietnamese), generic defaults, license files.

= 3.0.0 =
* Theia v2 engine without jQuery; load only where a sidebar is rendered; "Do not load on" page types; settings split into a non-autoloaded option and a tiny autoloaded runtime option.
