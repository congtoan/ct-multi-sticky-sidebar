/*! CT Multi Sticky Sidebar - init (plain JS, no jQuery). The settings are injected in place of the placeholder when the file is generated. */
(function () {
  var c = /*__LSS_CONFIG__*/{};
  if (!c.groups || typeof window.TheiaStickySidebar !== 'function') return;

  function run() {
    c.groups.forEach(function (g) {
      var mt = g.marginTop !== '' && g.marginTop != null ? +g.marginTop : +c.marginTop || 0;
      var mb = g.marginBottom !== '' && g.marginBottom != null ? +g.marginBottom : +c.marginBottom || 0;
      try { // an invalid selector in one group must not break the other groups
        var els = Array.prototype.slice.call(document.querySelectorAll(g.sidebar));
        if (!els.length) return;
        new window.TheiaStickySidebar({
          elements: els,
          containerSelector: g.container || '',
          additionalMarginTop: mt,
          additionalMarginBottom: mb,
          minWidth: +c.minWidth || 0,
          updateSidebarHeight: c.updateSidebarHeight === true,
          sidebarBehavior: c.sidebarBehavior || 'modern',
          disableOnResponsiveLayouts: c.disableOnResponsiveLayouts !== false
        });
      } catch (e) {
        if (window.console) console.warn('CT Multi Sticky Sidebar: skipped a group with an error', g.sidebar, e);
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
