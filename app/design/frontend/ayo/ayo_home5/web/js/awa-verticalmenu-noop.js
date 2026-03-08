/**
 * Noop replacement for Rokanthemes VerticalMenu jQuery plugin.
 * AWA vertical-menu-init.js handles all vertical menu functionality.
 */
define(['jquery'], function ($) {
    'use strict';
    $.fn.VerticalMenu = $.fn.VerticalMenu || function () { return this; };
});
