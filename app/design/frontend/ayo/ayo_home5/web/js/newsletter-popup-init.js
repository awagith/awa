define([
    'jquery'
], function ($) {
    'use strict';

    function setCookie(name, value, days) {
        var expires = '';
        var date;

        if (days) {
            date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }

        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
        var nameEq = name + '=';
        var cookies = document.cookie.split(';');
        var i;
        var item;

        for (i = 0; i < cookies.length; i++) {
            item = cookies[i].trim();
            if (item.indexOf(nameEq) === 0) {
                return item.substring(nameEq.length);
            }
        }

        return null;
    }

    return function (config, element) {
        var $popup = $(element);
        var cookieDays = parseInt(config.cookieDays, 10) || 30;
        var cookieName = config.cookieName || 'shownewsletter';
        var popupDelay = parseInt(config.popupDelay, 10) || 3000;
        var popupHeight = parseInt(config.popupHeight, 10) || 520;
        var popupSpeed = parseInt(config.popupSpeed, 10) || 450;
        var closeDelay = parseInt(config.closeDelay, 10) || 4000;
        var closeAnimation = parseInt(config.closeAnimation, 10) || 300;
        var focusDelay = parseInt(config.focusDelay, 10) || 500;
        var homeOnly = !!config.homeOnly;
        var sendingText = config.sendingText || 'Enviando...';
        var pPopupRef = null;
        var forcePreview;
        var effectiveDelay;

        if (!$popup.length || $popup.data('awaNewsletterInit')) {
            return;
        }

        $popup.data('awaNewsletterInit', 1);

        function closePopup() {
            $popup.addClass('popup-closing');

            setTimeout(function () {
                try {
                    if (pPopupRef && typeof pPopupRef.close === 'function') {
                        pPopupRef.close();
                    }
                } catch (error) {
                    // bPopup may already be destroyed; fallback below is enough.
                }

                $popup.hide()
                    .removeClass('popup-closing')
                    .removeClass('nl-popup-fallback-open');
                $popup.css({
                    display: '',
                    alignItems: '',
                    justifyContent: ''
                });
                $('body').removeClass('nl-popup-body-open');
                $(document).off('keydown.awaNewsletter');
            }, closeAnimation);
        }

        function showSuccess() {
            $popup.find('.nl-popup-form, .nl-popup-urgency, .nl-popup-footer')
                .fadeOut(200, function () {
                    $popup.find('.nl-popup-success').fadeIn(300).css('display', 'flex');
                });

            setTimeout(function () {
                closePopup();
            }, closeDelay);
        }

        function openPopup() {
            var fixedHeight = Math.max(40, ($(window).height() - popupHeight) / 2);

            if (typeof $popup.bPopup === 'function') {
                pPopupRef = $popup.bPopup({
                    position: ['auto', fixedHeight],
                    speed: popupSpeed,
                    transition: 'slideDown',
                    onClose: function () { }
                });
                return;
            }

            $popup.show().addClass('nl-popup-fallback-open');
            $popup.css({
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });
            $('body').addClass('nl-popup-body-open');
        }

        $popup.off('.awaNewsletter')
            .on('click.awaNewsletter', '.newletter_popup_close, .newletter_popup_close_text', function (event) {
                event.preventDefault();
                closePopup();
            })
            .on('click.awaNewsletter', function (event) {
                if ($(event.target).hasClass('newsletterpopup')) {
                    closePopup();
                }
            })
            .on('submit.awaNewsletter', '#newsletter-validate-popup', function (event) {
                var $form = $(this);
                var email = $form.find('#newsletter-popup').val();
                var isValid = true;

                event.preventDefault();

                if (typeof $form.validation === 'function') {
                    isValid = $form.validation('isValid');
                }

                if (!email || !isValid) {
                    return;
                }

                setCookie(cookieName, '1', cookieDays);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    beforeSend: function () {
                        $form.find('.action.subscribe')
                            .prop('disabled', true)
                            .find('span').text(sendingText);
                    },
                    complete: function () {
                        showSuccess();
                    }
                });
            })
            .on('change.awaNewsletter', '#newsletter_popup_dont_show_again', function () {
                setCookie(cookieName, this.checked ? '1' : '0', cookieDays);
            });

        forcePreview = window.location.search.indexOf('newsletter_preview=1') !== -1;
        effectiveDelay = forcePreview ? 120 : popupDelay;

        if (!forcePreview && homeOnly && !$('body').hasClass('cms-index-index')) {
            return;
        }

        if (!forcePreview && getCookie(cookieName) === '1') {
            return;
        }

        setTimeout(function () {
            if (!forcePreview && getCookie(cookieName) === '1') {
                return;
            }

            openPopup();

            $(document).off('keydown.awaNewsletter').on('keydown.awaNewsletter', function (event) {
                if (event.key === 'Escape' && $popup.is(':visible')) {
                    closePopup();
                    $(document).off('keydown.awaNewsletter');
                }
            });

            setTimeout(function () {
                $popup.find('#newsletter-popup').focus();
            }, focusDelay);
        }, effectiveDelay);
    };
});
