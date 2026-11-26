/*!
 * Theia Sticky Sidebar v2.0.0
 * https://github.com/WeCodePixels/theia-sticky-sidebar
 *
 * Glues your website's sidebars, making them permanently visible while scrolling.
 *
 * Copyright 2013-2025 WeCodePixels and other contributors
 * Released under the MIT license
 */
/*
 * Notes from CT Multi Sticky Sidebar:
 * - Hand-ported from lib/theia-sticky-sidebar.ts (tag v2.0.0) to plain JavaScript: TypeScript
 *   types removed, exported as window.TheiaStickySidebar. No jQuery dependency.
 * - Exactly one change from upstream: the ResizeObserver callback calls onScroll through
 *   requestAnimationFrame (marked "LSS PATCH"). onScroll changes the width of the very element
 *   being observed, and calling it synchronously easily triggers
 *   "ResizeObserver loop completed with undelivered notifications" in the console.
 */
(function (window, document) {
    'use strict';

    function getOffset(element) {
        var rect = element.getBoundingClientRect();
        return {
            top: rect.top + window.scrollY - document.documentElement.clientTop,
            left: rect.left + window.scrollX - document.documentElement.clientLeft
        };
    }

    function TheiaStickySidebar(options) {
        var self = this;
        options = options || {};

        var defaults = {
            elements: '',
            containerSelector: '',
            additionalMarginTop: 0,
            additionalMarginBottom: 0,
            updateSidebarHeight: true,
            minWidth: 0,
            disableOnResponsiveLayouts: true,
            sidebarBehavior: 'modern',
            defaultPosition: 'relative',
            verbose: false
        };
        var finalOptions = Object.assign({}, defaults, options);

        // Validate options
        finalOptions.additionalMarginTop = Math.floor(options.additionalMarginTop || 0);
        finalOptions.additionalMarginBottom = Math.floor(options.additionalMarginBottom || 0);

        if (finalOptions.elements instanceof HTMLElement) {
            this.elements = [finalOptions.elements];
        } else if (finalOptions.elements instanceof Array) {
            this.elements = finalOptions.elements;
        } else {
            this.elements = Array.from(document.querySelectorAll(finalOptions.elements));
        }

        this.options = finalOptions;
        this.initialized = false;
        this.stickySidebars = [];

        this.unbind = function () {
            document.removeEventListener('scroll', self.tryDelayedInit);
            window.removeEventListener('resize', self.tryDelayedInit);

            self.stickySidebars.forEach(function (o) {
                document.removeEventListener('scroll', o.onScroll);
                window.removeEventListener('resize', o.onScroll);
                o.resizeObserver.disconnect();
            });
        };

        // Try doing init, otherwise hook into window.resize and document.scroll and try again then.
        this.tryInitOrHookIntoEvents = function () {
            var success = self.tryInit();

            if (!success) {
                if (self.options.verbose) {
                    console.log('TSS: Body width smaller than options.minWidth. Init is delayed.');
                }

                document.addEventListener('scroll', self.tryDelayedInit);
                window.addEventListener('resize', self.tryDelayedInit);
            }
        };

        this.tryDelayedInit = function () {
            var success = self.tryInit();

            if (success) {
                document.removeEventListener('scroll', self.tryDelayedInit);
                window.removeEventListener('resize', self.tryDelayedInit);
            }
        };

        this.tryInitOrHookIntoEvents();
    }

    // Try doing init if proper conditions are met.
    TheiaStickySidebar.prototype.tryInit = function () {
        if (this.initialized) {
            return true;
        }

        if (document.body.getBoundingClientRect().width < this.options.minWidth) {
            return false;
        }

        this.init();

        return true;
    };

    // Init the sticky sidebar(s).
    TheiaStickySidebar.prototype.init = function () {
        var self = this;
        this.initialized = true;

        // Add CSS
        var existingStylesheet = document.querySelector('#theia-sticky-sidebar-stylesheet');
        if (!existingStylesheet) {
            document.head.insertAdjacentHTML('beforeend', '<style id="theia-sticky-sidebar-stylesheet">.theiaStickySidebar:after {content: ""; display: table; clear: both;}</style>');
        }

        this.elements.forEach(function (element) {
            var o = {};

            o.sidebar = element;

            // Save options
            o.options = self.options || {};

            // Get container
            o.container = (o.options.containerSelector && document.querySelector(o.options.containerSelector));
            if (!o.container) {
                o.container = o.sidebar.parentNode;
            }

            // Create sticky sidebar
            Object.assign(o.sidebar.style, {
                position: o.options.defaultPosition,
                overflow: 'visible',
                boxSizing: 'border-box'
            });

            // Get the sticky sidebar element. If none has been found, then create one.
            o.stickySidebar = o.sidebar.querySelector('.theiaStickySidebar');
            if (!o.stickySidebar) {
                // Remove <script> tags, otherwise they will be run again when added to the stickySidebar.
                var javaScriptMIMETypes = /(?:text|application)\/(?:x-)?(?:javascript|ecmascript)/i;
                Array.from(o.sidebar.querySelectorAll('script')).forEach(function (script) {
                    if (script.type.length === 0 || script.type.match(javaScriptMIMETypes)) {
                        script.remove();
                    }
                });

                o.stickySidebar = document.createElement('div');
                o.stickySidebar.classList.add('theiaStickySidebar');
                o.stickySidebar.append.apply(o.stickySidebar, Array.from(o.sidebar.children));
                o.sidebar.append(o.stickySidebar);
            }

            // Get existing top and bottom margins and paddings
            var computedStyle = getComputedStyle(o.sidebar);
            o.marginBottom = parseFloat(computedStyle.marginBottom);
            o.paddingTop = parseFloat(computedStyle.paddingTop);
            o.paddingBottom = parseFloat(computedStyle.paddingBottom);

            // Add a temporary padding rule to check for collapsable margins.
            var collapsedTopHeight = getOffset(o.stickySidebar).top;
            var collapsedBottomHeight = o.stickySidebar.offsetHeight;
            o.stickySidebar.style.paddingTop = '1px';
            o.stickySidebar.style.paddingBottom = '1px';
            collapsedTopHeight -= getOffset(o.stickySidebar).top;
            collapsedBottomHeight = o.stickySidebar.offsetHeight - collapsedBottomHeight - collapsedTopHeight;
            if (collapsedTopHeight == 0) {
                o.stickySidebar.style.paddingTop = '0px';
                o.stickySidebarPaddingTop = 0;
            } else {
                o.stickySidebarPaddingTop = 1;
            }

            if (collapsedBottomHeight == 0) {
                o.stickySidebar.style.paddingBottom = '0px';
                o.stickySidebarPaddingBottom = 0;
            } else {
                o.stickySidebarPaddingBottom = 1;
            }

            // We use this to know whether the user is scrolling up or down.
            o.previousScrollTop = 0;

            // Scroll top (value) when the sidebar has fixed position.
            o.fixedScrollTop = 0;

            // Set sidebar to default values.
            self.resetSidebar(o);

            o.onScroll = function () {
                // Stop if the sidebar isn't visible.
                if (!self.isVisible(o.stickySidebar)) {
                    return;
                }

                // Stop if the window is too small.
                if (document.body.getBoundingClientRect().width < o.options.minWidth) {
                    self.resetSidebar(o);
                    return;
                }

                // Stop if the sidebar width is larger than the container width (e.g. the theme is responsive and the sidebar is now below the content)
                if (o.options.disableOnResponsiveLayouts) {
                    var sidebarWidth = getComputedStyle(o.sidebar).float === 'none' ? self.getOuterWidth(o.sidebar) : o.sidebar.offsetWidth;

                    if (sidebarWidth + 50 > o.container.getBoundingClientRect().width) {
                        self.resetSidebar(o);
                        return;
                    }
                }

                var scrollTop = window.scrollY;
                var position = 'static';
                var sidebarOffset = getOffset(o.sidebar);
                var top = 0;

                // If the user has scrolled down enough for the sidebar to be clipped at the top, then we can consider changing its position.
                if (scrollTop >= sidebarOffset.top + (o.paddingTop - o.options.additionalMarginTop)) {
                    // The top and bottom offsets, used in various calculations.
                    var offsetTop = o.paddingTop + self.options.additionalMarginTop;
                    var offsetBottom = o.paddingBottom + o.marginBottom + self.options.additionalMarginBottom;

                    // All top and bottom positions are relative to the window, not to the parent elemnts.
                    var containerTop = sidebarOffset.top;
                    var containerBottom = getOffset(o.container).top + self.getClearedHeight(o.container);

                    // The top and bottom offsets relative to the window screen top (zero) and bottom (window height).
                    var windowOffsetTop = self.options.additionalMarginTop;
                    var windowOffsetBottom;

                    var sidebarSmallerThanWindow = (o.stickySidebar.offsetHeight + offsetTop + offsetBottom) < window.innerHeight;
                    if (sidebarSmallerThanWindow) {
                        windowOffsetBottom = windowOffsetTop + o.stickySidebar.offsetHeight;
                    } else {
                        windowOffsetBottom = window.innerHeight - o.marginBottom - o.paddingBottom - self.options.additionalMarginBottom;
                    }

                    var staticLimitTop = containerTop - scrollTop + o.paddingTop;
                    var staticLimitBottom = containerBottom - scrollTop - o.paddingBottom - o.marginBottom;

                    top = getOffset(o.stickySidebar).top - scrollTop;
                    var scrollTopDiff = o.previousScrollTop - scrollTop;

                    // If the sidebar position is fixed, then it won't move up or down by itself. So, we manually adjust the top coordinate.
                    if (getComputedStyle(o.stickySidebar).position === 'fixed') {
                        if (o.options.sidebarBehavior == 'modern') {
                            top += scrollTopDiff;
                        }
                    }

                    if (o.options.sidebarBehavior == 'stick-to-top') {
                        top = self.options.additionalMarginTop;
                    }

                    if (o.options.sidebarBehavior == 'stick-to-bottom') {
                        top = windowOffsetBottom - o.stickySidebar.offsetHeight;
                    }

                    if (scrollTopDiff > 0) { // If the user is scrolling up.
                        top = Math.min(top, windowOffsetTop);
                    } else { // If the user is scrolling down.
                        top = Math.max(top, windowOffsetBottom - o.stickySidebar.offsetHeight);
                    }

                    top = Math.max(top, staticLimitTop);

                    top = Math.min(top, staticLimitBottom - o.stickySidebar.offsetHeight);

                    // If the sidebar is the same height as the container, we won't use fixed positioning.
                    var sidebarSameHeightAsContainer = o.container.getBoundingClientRect().height == o.stickySidebar.offsetHeight;

                    if (!sidebarSameHeightAsContainer && top == windowOffsetTop) {
                        position = 'fixed';
                    } else if (!sidebarSameHeightAsContainer && top == windowOffsetBottom - o.stickySidebar.offsetHeight) {
                        position = 'fixed';
                    } else if (scrollTop + top - sidebarOffset.top - o.paddingTop <= self.options.additionalMarginTop) {
                        // Stuck to the top of the page. No special behavior.
                        position = 'static';
                    } else {
                        // Stuck to the bottom of the page.
                        position = 'absolute';
                    }
                }

                /*
                 * Performance notice: It's OK to set these CSS values at each resize/scroll, even if they don't change.
                 * It's way slower to first check if the values have changed.
                 */
                if (position == 'fixed') {
                    Object.assign(o.stickySidebar.style, {
                        position: 'fixed',
                        width: o.stickySidebar.getBoundingClientRect().width + 'px',
                        transform: 'translateY(' + top + 'px)',
                        left: (getOffset(o.sidebar).left + parseFloat(getComputedStyle(o.sidebar).paddingLeft) - window.scrollX) + 'px',
                        top: '0px'
                    });
                } else if (position == 'absolute') {
                    var css = {};

                    if (getComputedStyle(o.stickySidebar).position !== 'absolute') {
                        css.position = 'absolute';
                        css.transform = 'translateY(' + (scrollTop + top - sidebarOffset.top - o.stickySidebarPaddingTop - o.stickySidebarPaddingBottom) + 'px)';
                        css.top = '0px';
                    }

                    css.width = o.stickySidebar.getBoundingClientRect().width + 'px';
                    css.left = '';

                    Object.assign(o.stickySidebar.style, css);
                } else if (position == 'static') {
                    self.resetSidebar(o);
                }

                if (position != 'static') {
                    if (o.options.updateSidebarHeight) {
                        o.sidebar.style.minHeight = (o.stickySidebar.offsetHeight + getOffset(o.stickySidebar).top - sidebarOffset.top + o.paddingBottom) + 'px';
                    }
                }

                o.previousScrollTop = scrollTop;
            };

            // Initialize the sidebar's position.
            o.onScroll();

            // Recalculate the sidebar's position on every scroll and resize.
            document.addEventListener('scroll', o.onScroll);
            window.addEventListener('resize', o.onScroll);

            // Recalculate the sidebar's position every time the sidebar changes its size.
            // LSS PATCH: call through requestAnimationFrame (coalesces several size changes into one
            // frame and avoids "ResizeObserver loop completed with undelivered notifications").
            var rafPending = false;
            o.resizeObserver = new ResizeObserver(function () {
                if (rafPending) return;
                rafPending = true;
                window.requestAnimationFrame(function () {
                    rafPending = false;
                    o.onScroll();
                });
            });
            o.resizeObserver.observe(o.stickySidebar);

            self.stickySidebars.push(o);
        });
    };

    TheiaStickySidebar.prototype.getOuterWidth = function (element) {
        var style = getComputedStyle(element);

        return element.getBoundingClientRect().width + parseFloat(style.marginLeft) + parseFloat(style.marginRight);
    };

    TheiaStickySidebar.prototype.isVisible = function (element) {
        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    };

    // Reset the sidebar to its default state
    TheiaStickySidebar.prototype.resetSidebar = function (s) {
        s.fixedScrollTop = 0;
        s.sidebar.style.minHeight = '1px';
        Object.assign(s.stickySidebar.style, {
            'position': 'static',
            'width': '',
            'transform': 'none'
        });
    };

    // Get the height of a div as if its floated children were cleared. Note that this function fails if the floats are more than one level deep.
    TheiaStickySidebar.prototype.getClearedHeight = function (element) {
        var height = element.getBoundingClientRect().height;

        Array.from(element.children).forEach(function (child) {
            height = Math.max(height, child.getBoundingClientRect().height);
        });

        return height;
    };

    TheiaStickySidebar.getOffset = getOffset;
    window.TheiaStickySidebar = TheiaStickySidebar;
})(window, document);
