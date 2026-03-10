/**
 * OWL Carousel v1 visibility fix.
 *
 * OWL v1 calculates item widths during init. When a carousel lives inside a
 * CSS `content-visibility: auto` section that hasn't been rendered yet (below
 * the fold), the container reports width = 0 and OWL stores itemWidth = 0,
 * resulting in invisible / collapsed carousel items.
 *
 * This mixin wraps $.fn.owlCarousel so that after every init call it checks
 * whether OWL computed a zero width. If so, an IntersectionObserver watches
 * the element and triggers owl.reload() once the carousel enters the viewport
 * (i.e. when the browser renders the content-visibility section).
 */
define(['jquery'], function ($) {
    'use strict';

    return function (owlModule) {
        var originalOwlCarousel = $.fn.owlCarousel;
        var wrappedOwlCarousel;

        if (typeof originalOwlCarousel !== 'function') {
            return owlModule;
        }

        wrappedOwlCarousel = function () {
            var result = originalOwlCarousel.apply(this, arguments);

            if (typeof IntersectionObserver === 'undefined') {
                return result;
            }

            this.each(function () {
                var el = this;
                var owl = $.data(el, 'owlCarousel');

                if (!owl || owl.itemWidth > 0) {
                    return;
                }

                var observer = new IntersectionObserver(function (entries) {
                    var i, entry, inst;

                    for (i = 0; i < entries.length; i++) {
                        entry = entries[i];

                        if (!entry.isIntersecting) {
                            continue;
                        }

                        observer.unobserve(entry.target);

                        // Allow one animation frame for the browser to finish
                        // rendering the content-visibility section.
                        requestAnimationFrame(function () {
                            inst = $.data(entry.target, 'owlCarousel');

                            if (inst && inst.itemWidth === 0) {
                                inst.reload();
                            }
                        });
                    }
                }, {rootMargin: '300px'});

                observer.observe(el);
            });

            return result;
        };

        // Preserve OWL static defaults/properties used by the plugin internals.
        wrappedOwlCarousel.options = originalOwlCarousel.options;
        wrappedOwlCarousel.owl = originalOwlCarousel.owl;
        wrappedOwlCarousel.noConflict = originalOwlCarousel.noConflict;
        $.fn.owlCarousel = wrappedOwlCarousel;

        return owlModule;
    };
});
