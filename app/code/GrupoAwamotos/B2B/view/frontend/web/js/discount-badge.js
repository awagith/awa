define(["jquery"], function ($) {
    "use strict";

    /**
     * Accessible toggle for B2B Discount Badge tooltip
     * Usage via data-mage-init on container element.
     */
    return function (config, element) {
        var $root = $(element);
        var tooltipId = (config && config.tooltipId) ? String(config.tooltipId) : "b2b-badge-tooltip";
        var $tooltip = $root.find("#" + tooltipId);

        function setExpanded(state) {
            var expanded = !!state;
            $root.attr("aria-expanded", expanded ? "true" : "false");
            if ($tooltip.length) {
                $tooltip.attr("aria-hidden", expanded ? "false" : "true");
            }
        }

        // Click toggles
        $root.on("click", function (e) {
            // Ignore clicks on links inside tooltip; let them work normally
            var isLink = $(e.target).closest("a").length > 0;
            if (isLink) {
                return;
            }
            var expanded = $root.attr("aria-expanded") === "true";
            setExpanded(!expanded);
        });

        // Keyboard interactions
        $root.on("keydown", function (e) {
            var key = e.key || e.which;
            if (key === "Enter" || key === 13 || key === " ") { // Enter or Space
                e.preventDefault();
                var expanded = $root.attr("aria-expanded") === "true";
                setExpanded(!expanded);
            } else if (key === "Escape" || key === 27) { // Escape
                setExpanded(false);
            }
        });

        // Dismiss when clicking outside
        $(document).on('click.b2b-discount-badge', function (e) {
            if (!$root.is(e.target) && $root.has(e.target).length === 0) {
                setExpanded(false);
            }
        });

        // Initialize collapsed
        setExpanded(false);

        return {
            destroy: function() {
                $(document).off('click.b2b-discount-badge');
            }
        };
    };
});
