/**
 * Newsletter Popup Widget
 * Exit-intent e time-delay trigger
 */
define([
    'jquery',
    'jquery-ui-modules/widget',
    'mage/cookies'
], function ($) {
    'use strict';

    $.widget('mage.newsletterPopup', {
        options: {
            cookieName: 'newsletter_popup_shown',
            cookieLifetime: 30, // dias
            triggerDelay: 30000, // 30 segundos
            exitIntentEnabled: true,
            exitIntentThreshold: 50 // pixels do topo
        },

        _create: function () {
            this._bind();
        },

        _bind: function () {
            var self = this;
            
            // Verificar se popup já foi mostrado
            if (this._hasSeenPopup()) {
                return;
            }

            // Trigger 1: Delay timer (30s)
            setTimeout(function () {
                if (!self._hasSeenPopup()) {
                    self._showPopup();
                }
            }, this.options.triggerDelay);

            // Trigger 2: Exit intent
            if (this.options.exitIntentEnabled) {
                $(document).on('mouseleave', function (e) {
                    if (e.clientY < self.options.exitIntentThreshold && !self._hasSeenPopup()) {
                        self._showPopup();
                    }
                });
            }

            // Close handlers
            this.element.find('[data-action="close-popup"]').on('click', function (e) {
                e.preventDefault();
                self._closePopup();
            });

            // Form submit
            this.element.find('#newsletter-popup-form').on('submit', function (e) {
                e.preventDefault();
                self._handleSubmit($(this));
            });

            // ESC key
            $(document).on('keydown', function (e) {
                if (e.keyCode === 27 && self.element.hasClass('active')) {
                    self._closePopup();
                }
            });
        },

        _showPopup: function () {
            this.element.addClass('active');
            $('body').css('overflow', 'hidden');
            this._setPopupCookie();
            
            // Analytics event
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': 'newsletter_popup_shown',
                    'eventCategory': 'Newsletter',
                    'eventAction': 'Popup Shown'
                });
            }
        },

        _closePopup: function () {
            var self = this;
            this.element.removeClass('active');
            
            setTimeout(function () {
                $('body').css('overflow', '');
            }, 300);
            
            // Analytics event
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': 'newsletter_popup_closed',
                    'eventCategory': 'Newsletter',
                    'eventAction': 'Popup Closed'
                });
            }
        },

        _handleSubmit: function ($form) {
            var self = this;
            
            if (!$form.validation('isValid')) {
                return;
            }

            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.find('span').text();
            
            $submitBtn.prop('disabled', true);
            $submitBtn.find('span').text('Enviando...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    // Mostrar mensagem de sucesso
                    self._showSuccessMessage();
                    
                    // Analytics event
                    if (typeof dataLayer !== 'undefined') {
                        dataLayer.push({
                            'event': 'newsletter_signup',
                            'eventCategory': 'Newsletter',
                            'eventAction': 'Signup Success',
                            'eventLabel': 'Popup'
                        });
                    }
                    
                    // Fechar após 3 segundos
                    setTimeout(function () {
                        self._closePopup();
                    }, 3000);
                },
                error: function () {
                    alert('Erro ao cadastrar. Por favor, tente novamente.');
                    $submitBtn.prop('disabled', false);
                    $submitBtn.find('span').text(originalText);
                }
            });
        },

        _showSuccessMessage: function () {
            var $popupBody = this.element.find('.popup-body');
            
            $popupBody.html(
                '<div class="success-message" style="text-align:center; padding:40px 20px;">' +
                    '<i class="fa fa-check-circle" style="font-size:64px; color:#4CAF50; margin-bottom:20px;"></i>' +
                    '<h3 style="color:#333; margin-bottom:10px;">Parabéns! 🎉</h3>' +
                    '<p style="color:#666; font-size:16px; margin-bottom:15px;">Você ganhou <strong style="color:#b73337;">10% OFF</strong>!</p>' +
                    '<p style="color:#999; font-size:14px;">Enviamos um cupom para seu e-mail.</p>' +
                '</div>'
            );
        },

        _hasSeenPopup: function () {
            return $.mage.cookies.get(this.options.cookieName) === '1';
        },

        _setPopupCookie: function () {
            var expires = new Date();
            expires.setTime(expires.getTime() + (this.options.cookieLifetime * 24 * 60 * 60 * 1000));
            
            $.mage.cookies.set(this.options.cookieName, '1', {
                expires: expires,
                path: '/'
            });
        }
    });

    return $.mage.newsletterPopup;
});
