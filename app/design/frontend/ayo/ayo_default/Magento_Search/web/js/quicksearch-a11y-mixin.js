/*eslint-env amd, browser*/
define([
    'jquery'
], function ($) {
    'use strict';

    function isElementVisible($el) {
        return $el.length && $el.is(':visible');
    }

    return function (quickSearchWidget) {
        $.widget('mage.quickSearch', quickSearchWidget, {
            _create: function () {
                this._super();

                // Keep aria-expanded in sync with the suggestion popup.
                this._initA11yExpandedSync();

                // Hide suggestions immediately when clearing or going below minSearchLength.
                // (The native quickSearch uses a debounced handler, so we complement it here.)
                this.element.on('input.quicksearchA11y search.quicksearchA11y', function () {
                    var value = (this.element.val() || '').trim();

                    // User typed/edited; consider this the canonical typed value.
                    this._a11yPreNavValue = null;

                    if (value.length < parseInt(this.options.minSearchLength, 10)) {
                        this._hideSuggestionsA11y();
                    }
                }.bind(this));

                // Refresh suggestions when category changes in SearchByCat dropdown.
                $(document).on('change.quicksearchA11y', '#choose_category', function () {
                    var value = (this.element.val() || '').trim();

                    if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                        this._onPropertyChange();
                    } else {
                        this._hideSuggestionsA11y();
                    }
                }.bind(this));

                // When user clicks an option, prevent the input from losing focus on mousedown.
                // The native quickSearch attaches a click handler on the <li> to submit.
                // Hiding/clearing the dropdown on mousedown can cancel the click, so we only
                // prevent default here to avoid blur/flicker.
                if (this.autoComplete && this.autoComplete.length) {
                    this.autoComplete.on('mousedown.quicksearchA11y', '[role="option"]', function (e) {
                        e.preventDefault();
                    }.bind(this));
                }

                // Close suggestions when clicking outside search form/autocomplete.
                $(document).on('mousedown.quicksearchA11y touchstart.quicksearchA11y', function (e) {
                    var $target = $(e.target);

                    if (!$target.closest(this.searchForm).length && !$target.closest(this.autoComplete).length) {
                        this._hideSuggestionsA11y();
                    }
                }.bind(this));
            },

            /**
             * Intercepta keydown antes do handler original para:
             * - Capturar o valor digitado antes da navegação com setas (o widget troca o value)
             * - Fechar sugestões com TAB sem forçar foco
             * - No ESC, restaurar o valor digitado e fechar
             *
             * @private
             */
            _onKeyDown: function (e) {
                var keyCode = e.keyCode || e.which,
                    isNavKey = keyCode === $.ui.keyCode.DOWN ||
                        keyCode === $.ui.keyCode.UP ||
                        keyCode === $.ui.keyCode.HOME ||
                        keyCode === $.ui.keyCode.END;

                // Store typed value once when user starts navigating suggestions.
                if (isNavKey && this._a11yPreNavValue === null) {
                    this._a11yPreNavValue = this.element.val();
                }

                // Close suggestions early on TAB (prevents focus being forced back to input).
                if (keyCode === 9) { // TAB
                    this._hideSuggestionsA11y();
                    return true;
                }

                // Let the original widget handle all standard keys.
                this._super(e);

                // On ESC, restore the original typed value (undo arrow selection).
                if (keyCode === $.ui.keyCode.ESCAPE) {
                    if (this._a11yPreNavValue !== null) {
                        this.element.val(this._a11yPreNavValue);
                    }
                    this._a11yPreNavValue = null;
                    this._hideSuggestionsA11y();
                }

                return true;
            },

            /**
             * Hides suggestions and resets ARIA attributes.
             *
             * @private
             */
            _hideSuggestionsA11y: function () {
                if (!this.autoComplete || !this.autoComplete.length) {
                    return;
                }

                this.autoComplete.hide().empty();

                if (typeof this._updateAriaHasPopup === 'function') {
                    this._updateAriaHasPopup(false);
                }

                this.element
                    .attr('aria-expanded', 'false')
                    .removeAttr('aria-activedescendant');
            },

            /**
             * Observa o container de autocomplete para sincronizar aria-expanded.
             * Evita depender de internals do widget (Ajax/async).
             *
             * @private
             */
            _initA11yExpandedSync: function () {
                if (!this.autoComplete || !this.autoComplete.length || typeof MutationObserver === 'undefined') {
                    return;
                }

                var observer = new MutationObserver(function () {
                    this._syncExpandedA11y();
                }.bind(this));

                observer.observe(this.autoComplete.get(0), {
                    childList: true,
                    subtree: true,
                    characterData: true
                });

                this._a11yObserver = observer;
                this._syncExpandedA11y();
            },

            /**
             * @private
             */
            _syncExpandedA11y: function () {
                var hasOptions = this.autoComplete.find('[role="option"]').length > 0,
                    visible = isElementVisible(this.autoComplete);

                this.element.attr('aria-expanded', (visible && hasOptions) ? 'true' : 'false');
            },

            /**
             * Ensure quickSearch AJAX request carries category filter when available.
             *
             * @private
             * @return {Boolean|undefined}
             */
            _onPropertyChange: function () {
                this.options.url = this._getQuickSearchUrlWithCategory(this.options.url);

                return this._super();
            },

            /**
             * @private
             * @param {String} url
             * @return {String}
             */
            _getQuickSearchUrlWithCategory: function (url) {
                var cat = $('#choose_category').val(),
                    cleanUrl;

                if (!url) {
                    return url;
                }

                cleanUrl = url
                    .replace(/([?&])cat=[^&]*&?/g, '$1')
                    .replace(/[?&]$/, '');

                if (!cat) {
                    return cleanUrl;
                }

                return cleanUrl + (cleanUrl.indexOf('?') === -1 ? '?' : '&') + 'cat=' + encodeURIComponent(cat);
            },

            _destroy: function () {
                this.element.off('.quicksearchA11y');
                $(document).off('.quicksearchA11y');

                if (this.autoComplete && this.autoComplete.length) {
                    this.autoComplete.off('.quicksearchA11y');
                }

                this._a11yPreNavValue = null;

                if (this._a11yObserver) {
                    this._a11yObserver.disconnect();
                    this._a11yObserver = null;
                }

                this._super();
            }
        });

        return $.mage.quickSearch;
    };
});
