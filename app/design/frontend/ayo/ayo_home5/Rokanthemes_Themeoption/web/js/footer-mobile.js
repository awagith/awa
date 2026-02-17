define([
    'jquery',
    'mage/mage',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    var accordionEnabled = null;
    var expandedAccordionKeys = {};
    var isInitialized = false;

    function initBrandCarousel() {
        var $carousel = $('.block-content.brandowl-play > ul');
        if (!$carousel.length || typeof $carousel.owlCarousel !== 'function') {
            return;
        }

        $carousel.owlCarousel({
            lazyLoad: true,
            items: 7,
            itemsDesktop: [1366, 5],
            itemsDesktopSmall: [991, 3],
            itemsTablet: [767, 2],
            itemsMobile: [479, 1],
            navigation: true,
            afterAction: function () {
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });
    }

    function applyA11y($title, $content, idx) {
        if (!$content.length) {
            return;
        }

        var tagName = $title.prop('tagName');
        tagName = tagName ? tagName.toLowerCase() : '';

        // If the CMS content uses <button>, ensure it won't submit forms.
        if (tagName === 'button' && !$title.attr('type')) {
            $title.attr('type', 'button');
        }

        // Prefer native button semantics if the CMS content uses <button>.
        if (tagName !== 'button') {
            if (!$title.attr('role')) {
                $title.attr('role', 'button');
            }
            if (!$title.attr('tabindex')) {
                $title.attr('tabindex', '0');
            }
        }

        var contentId = $content.attr('id');
        if (!contentId) {
            contentId = 'footer-accordion-content-' + idx;
            $content.attr('id', contentId);
        }
        if (!$title.attr('aria-controls')) {
            $title.attr('aria-controls', contentId);
        }
    }

    function getFooterTitleText($title) {
        var cached = $title.data('footerTitleText');
        if (cached) {
            return cached;
        }

        var rawText = ($title.text() || '').replace(/\s+/g, ' ').trim();
        $title.data('footerTitleText', rawText);
        return rawText;
    }

    function updateFooterTitleAriaLabel($title, expanded) {
        // Only set aria-label if not already provided by CMS.
        if ($title.attr('aria-label') && !$title.data('footerAddedAriaLabel')) {
            return;
        }

        var titleText = getFooterTitleText($title);
        if (!titleText) {
            return;
        }

        var actionText = expanded ? 'Recolher' : 'Expandir';
        $title.attr('aria-label', actionText + ': ' + titleText);
        $title.data('footerAddedAriaLabel', true);
    }

    function clearFooterTitleAriaLabelIfAdded($title) {
        if ($title.data('footerAddedAriaLabel')) {
            $title.removeAttr('aria-label');
            $title.removeData('footerAddedAriaLabel');
        }
    }

    function prefersReducedMotion() {
        if (!window.matchMedia) {
            return false;
        }

        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function setExpandedState($title, $content, expanded) {
        $title.toggleClass('active', expanded);
        $title.attr('aria-expanded', expanded ? 'true' : 'false');
        $content.attr('aria-hidden', expanded ? 'false' : 'true');
    }

    function enableAccordion() {
        var $titles = $('.velaFooterMenu .velaFooterTitle');
        var reduceMotion = prefersReducedMotion();

        $titles.each(function (idx) {
            var $title = $(this);
            var $content = $title.closest('.velaFooterMenu').find('.velaContent').first();
            applyA11y($title, $content, idx);

            var key = $title.attr('aria-controls') || String(idx);
            var shouldExpand = !!expandedAccordionKeys[key];

            // Default collapsed on mobile (unless user had expanded before switching breakpoints)
            setExpandedState($title, $content, shouldExpand);
            updateFooterTitleAriaLabel($title, shouldExpand);
            $content.stop(true, true);
            if (shouldExpand) {
                $content.show();
            } else {
                $content.hide();
            }
        });

        $titles.off('.accordionFooter')
            .on('click.accordionFooter', function (e) {
                var $title = $(this);
                var $content = $title.closest('.velaFooterMenu').find('.velaContent').first();
                if (!$content.length) {
                    return;
                }

                var expanded = $title.hasClass('active');
                setExpandedState($title, $content, !expanded);
                updateFooterTitleAriaLabel($title, !expanded);

                if (reduceMotion) {
                    $content.stop(true, true);
                    if (expanded) {
                        $content.hide();
                    } else {
                        $content.show();
                    }
                } else {
                    if (expanded) {
                        $content.stop(true, true).slideUp('medium');
                    } else {
                        $content.stop(true, true).slideDown('medium');
                    }
                }

                e.preventDefault();
            })
            .on('keydown.accordionFooter', function (e) {
                var key = e.key || e.keyCode;
                if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                    $(this).trigger('click');
                    e.preventDefault();
                }
            });
    }

    function disableAccordion() {
        var $titles = $('.velaFooterMenu .velaFooterTitle');

        // Remember expanded sections so we can restore on mobile.
        expandedAccordionKeys = {};
        $titles.each(function (idx) {
            var $title = $(this);
            var key = $title.attr('aria-controls') || String(idx);
            if ($title.hasClass('active') || $title.attr('aria-expanded') === 'true') {
                expandedAccordionKeys[key] = true;
            }
        });

        $titles.off('.accordionFooter').each(function () {
            var $title = $(this);
            clearFooterTitleAriaLabelIfAdded($title);
        }).removeClass('active').attr('aria-expanded', 'true');

        var $contents = $('.velaFooterMenu .velaContent');
        $contents.attr('aria-hidden', 'false').stop(true, true).show();
    }

    function isNavOpen() {
        var docEl = document.documentElement;
        var htmlOpen = !!(docEl && docEl.classList && docEl.classList.contains('nav-open'));
        return htmlOpen || $('body').hasClass('nav-open');
    }

    function ensureNavSectionsId() {
        var $navSections = $('.nav-sections').first();
        if (!$navSections.length) {
            return null;
        }

        if (!$navSections.attr('id')) {
            $navSections.attr('id', 'nav-sections');
        }

        return $navSections.attr('id');
    }

    function syncFooterMenuToggleState($toggle) {
        if (!$toggle || !$toggle.length) {
            return;
        }

        var open = isNavOpen();
        $toggle.attr('aria-expanded', open ? 'true' : 'false');

        // Keep aria-label aligned with state (pt-BR).
        var currentLabel = ($toggle.attr('aria-label') || '').trim();
        if (!currentLabel) {
            $toggle.attr('aria-label', open ? 'Fechar menu' : 'Abrir menu');
        } else if (currentLabel === 'Abrir menu' || currentLabel === 'Fechar menu') {
            $toggle.attr('aria-label', open ? 'Fechar menu' : 'Abrir menu');
        }
    }

    function bindFixedBottomMenu() {
        var $toggle = $('.fixed-bottom .toggle-nav-footer');
        if (!$toggle.length) {
            return;
        }

        var navSectionsId = ensureNavSectionsId();
        if (navSectionsId && !$toggle.attr('aria-controls')) {
            $toggle.attr('aria-controls', navSectionsId);
        }

        // Keep aria-expanded in sync with the real nav open state.
        syncFooterMenuToggleState($toggle);

        var navObserver = null;
        if (window.MutationObserver) {
            navObserver = new MutationObserver(function () {
                syncFooterMenuToggleState($toggle);
            });

            try {
                navObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            } catch (e) {
                // Ignore observer failures (older browsers / restricted environments)
            }

            try {
                navObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
            } catch (e2) {
                // Ignore observer failures
            }
        }

        $toggle.off('.fixedBottom')
            .on('click.fixedBottom', function (e) {
                // Prevent jump-to-top from href="#" while keeping other handlers.
                e.preventDefault();

                var wasOpen = isNavOpen();

                // Give theme handlers a chance to open/close the menu.
                setTimeout(function () {
                    var isOpenNow = isNavOpen();

                    // If nothing changed, fallback to Magento's standard nav toggle.
                    if (isOpenNow === wasOpen) {
                        var $navToggle = $('.action.nav-toggle, .nav-toggle').first();
                        if ($navToggle.length) {
                            $navToggle.trigger('click');
                        }
                    }

                    syncFooterMenuToggleState($toggle);
                }, 60);
            })
            .on('keydown.fixedBottom', function (e) {
                var key = e.key || e.keyCode;
                if (key === ' ' || key === 32) {
                    // Space should activate like a button.
                    $(this).trigger('click');
                    e.preventDefault();
                }
            });
    }

    function updateFixedBottomPadding() {
        var shouldEnable = $(window).width() <= 767;
        var $fixedBottom = $('.fixed-bottom').first();

        if (shouldEnable && $fixedBottom.length) {
            $('body').addClass('has-fixed-bottom');

            // Use real fixed-bottom height to avoid content being hidden behind it.
            var fixedHeight = $fixedBottom.outerHeight();
            if (!fixedHeight || fixedHeight < 1) {
                fixedHeight = 70;
            }

            try {
                document.body.style.setProperty('--fixed-bottom-height', fixedHeight + 'px');
            } catch (e) {
                // Ignore style set failures
            }
        } else {
            $('body').removeClass('has-fixed-bottom');

            try {
                document.body.style.removeProperty('--fixed-bottom-height');
            } catch (e2) {
                // Ignore style remove failures
            }
        }
    }

    function responsiveResize() {
        var shouldEnable = $(window).width() <= 767;
        var breakpointChanged = (accordionEnabled !== shouldEnable);

        if (breakpointChanged) {
            accordionEnabled = shouldEnable;
            if (shouldEnable) {
                enableAccordion();
            } else {
                disableAccordion();
            }
        }

        // Always keep fixed-bottom compensation up to date on resize.
        updateFixedBottomPadding();
    }

    return function () {
        $(function () {
            if (isInitialized) {
                return;
            }
            isInitialized = true;

            initBrandCarousel();

            bindFixedBottomMenu();
            updateFixedBottomPadding();

            responsiveResize();
            $(window)
                .on('resize.footerMobile', responsiveResize)
                .on('orientationchange.footerMobile', function () {
                    // orientationchange may not always trigger resize consistently
                    responsiveResize();
                })
                .on('load.footerMobile', function () {
                    // ensure fixed-bottom height is correct after resources/layout settle
                    updateFixedBottomPadding();
                });
        });
    };
});
