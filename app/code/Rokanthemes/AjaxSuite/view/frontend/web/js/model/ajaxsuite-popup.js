define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return {
        modalWindow: null,
        ghostCleanupTimer: null,

        hasPopupContent: function (element) {
            var $element = $(element || this.modalWindow);
            var contentNode;
            var rawText;

            if (!$element.length) {
                return false;
            }

            contentNode = $element.get(0);

            if (!contentNode) {
                return false;
            }

            if (contentNode.querySelector('#mb-ajaxsuite-popup-wrapper') &&
                contentNode.querySelector('#mb-ajaxsuite-popup-wrapper').children.length) {
                return true;
            }

            if (contentNode.querySelector('.wrapper-success, .block-authentication, form, img, button, .action')) {
                return true;
            }

            rawText = (contentNode.textContent || '').replace(/\s+/g, ' ').trim();

            return rawText.length > 0;
        },

        cleanupGhostOverlay: function () {
            var $overlay = $('.modals-overlay');

            if ($('.modal-popup._show').length) {
                return;
            }

            $overlay.removeClass('_show').hide();
            $('body').removeClass('_has-modal').css('overflow', '');
        },

        /**
         * Create popUp window for provided element
         *
         * @param {HTMLElement} element
         */
        createPopUp: function (element) {
            var options = {
                'type': 'popup',
                'modalClass': 'popup-ajaxsuite ajaxsuite-popup-wrapper',
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };

            if (!this.hasPopupContent(element)) {
                this.modalWindow = element || null;
                return false;
            }

            this.modalWindow = element;
            modal(options, $(this.modalWindow));
            return true;
        },

        /** Show login popup window */
        showModal: function () {
            var self = this;

            if (!this.modalWindow || !this.hasPopupContent(this.modalWindow)) {
                this.cleanupGhostOverlay();
                return false;
            }

            $(this.modalWindow).modal('openModal').trigger('contentUpdated');

            if (this.ghostCleanupTimer) {
                window.clearTimeout(this.ghostCleanupTimer);
            }

            this.ghostCleanupTimer = window.setTimeout(function () {
                self.ghostCleanupTimer = null;

                if (!self.hasPopupContent(self.modalWindow)) {
                    self.hideModal();
                    self.cleanupGhostOverlay();
                }
            }, 180);

            return true;
        },
        hideModal: function () {
            if(this.modalWindow)
            {
                $(this.modalWindow).modal('closeModal');
            }

            this.cleanupGhostOverlay();
        }
    };
});
