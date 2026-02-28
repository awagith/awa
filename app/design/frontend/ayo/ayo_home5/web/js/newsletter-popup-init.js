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
        var smartTrigger = config.smartTrigger !== false;
        var scrollPercent = Math.max(1, parseInt(config.scrollPercent, 10) || 12);
        var minScrollPx = Math.max(120, parseInt(config.minScrollPx, 10) || 420);
        var exitIntentEnabled = config.exitIntent !== false;
        var sendingText = config.sendingText || 'Enviando...';
        var pPopupRef = null;
        var forcePreview;
        var effectiveDelay;
        var triggerReadyDelay;
        var fallbackOpenDelay;
        var isHomePage;
        var openRequested = false;
        var triggerBound = false;
        var triggerReadyTimer = null;
        var fallbackOpenTimer = null;
        var scrollTicking = false;
        var ghostGuardTimer = null;
        var overlayObserver = null;

        if (!$popup.length || $popup.data('awaNewsletterInit')) {
            return;
        }

        $popup.data('awaNewsletterInit', 1);

        function clearGhostGuardTimer() {
            if (!ghostGuardTimer) {
                return;
            }

            window.clearTimeout(ghostGuardTimer);
            ghostGuardTimer = null;
        }

        function getPopupCard() {
            return $popup.find('.nl-popup-card').get(0) || null;
        }

        function isNodeVisible(node, minWidth, minHeight) {
            var rect;
            var style;

            if (!node) {
                return false;
            }

            rect = node.getBoundingClientRect();
            style = window.getComputedStyle(node);

            if (!rect || rect.width < (minWidth || 1) || rect.height < (minHeight || 1)) {
                return false;
            }

            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        }

        function cleanupBPopupArtifacts() {
            $('.b-modal').each(function () {
                var $overlay = $(this);

                $overlay.removeClass('_show').css({
                    display: 'none',
                    opacity: ''
                });
            });
        }

        function closeGhostPopupIfNeeded() {
            var popupNode = $popup.get(0);
            var cardNode = getPopupCard();

            if (!isNodeVisible(popupNode, 40, 40)) {
                return;
            }

            if (isNodeVisible(cardNode, 120, 120)) {
                return;
            }

            clearGhostGuardTimer();
            cleanupBPopupArtifacts();
            $popup.hide()
                .removeClass('popup-closing')
                .removeClass('nl-popup-fallback-open');
            $popup.css({
                display: '',
                alignItems: '',
                justifyContent: '',
                opacity: '',
                visibility: '',
                top: '',
                left: ''
            });
            $('body').removeClass('nl-popup-body-open');
            $(document).off('keydown.awaNewsletter');
        }

        function scheduleGhostGuard() {
            clearGhostGuardTimer();

            ghostGuardTimer = window.setTimeout(function () {
                ghostGuardTimer = null;
                closeGhostPopupIfNeeded();
            }, 220);
        }

        function preparePopupForOpen() {
            $popup.removeClass('popup-closing')
                .removeClass('nl-popup-fallback-open');
            $popup.css({
                opacity: '',
                visibility: ''
            });

            cleanupBPopupArtifacts();
        }

        function ensureOverlayObserver() {
            if (overlayObserver || typeof MutationObserver === 'undefined') {
                return;
            }

            overlayObserver = new MutationObserver(function () {
                closeGhostPopupIfNeeded();
            });

            overlayObserver.observe(document.body, {
                childList: true,
                subtree: false
            });

            overlayObserver.observe($popup.get(0), {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        }

        function closePopup() {
            clearGhostGuardTimer();
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
                    justifyContent: '',
                    opacity: '',
                    visibility: '',
                    top: '',
                    left: ''
                });
                $('body').removeClass('nl-popup-body-open');
                cleanupBPopupArtifacts();
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

            preparePopupForOpen();

            if (typeof $popup.bPopup === 'function') {
                pPopupRef = $popup.bPopup({
                    position: ['auto', fixedHeight],
                    speed: popupSpeed,
                    transition: 'slideDown',
                    onOpen: function () {
                        $('body').addClass('nl-popup-body-open');
                        scheduleGhostGuard();
                    },
                    onClose: function () {
                        $('body').removeClass('nl-popup-body-open');
                        cleanupBPopupArtifacts();
                    }
                });
                scheduleGhostGuard();
                return;
            }

            $popup.show().addClass('nl-popup-fallback-open');
            $popup.css({
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });
            $('body').addClass('nl-popup-body-open');
            scheduleGhostGuard();
        }

        function shouldAbortOpen() {
            return getCookie(cookieName) === '1' || $popup.is(':visible') || openRequested;
        }

        function getScrollProgress() {
            var doc = document.documentElement;
            var body = document.body;
            var scrollTop = window.pageYOffset || doc.scrollTop || body.scrollTop || 0;
            var viewportHeight = window.innerHeight || doc.clientHeight || 0;
            var scrollHeight = Math.max(
                doc.scrollHeight,
                body.scrollHeight,
                doc.offsetHeight,
                body.offsetHeight
            );
            var totalScrollable = Math.max(1, scrollHeight - viewportHeight);

            return {
                top: scrollTop,
                percent: (scrollTop / totalScrollable) * 100
            };
        }

        function unbindSmartOpenTriggers() {
            if (!triggerBound) {
                return;
            }

            triggerBound = false;
            $(window).off('scroll.awaNewsletterTrigger');
            $(document).off('mouseout.awaNewsletterTrigger mouseleave.awaNewsletterTrigger');
        }

        function requestOpen(source) {
            if (shouldAbortOpen()) {
                return;
            }

            openRequested = true;
            unbindSmartOpenTriggers();
            clearTimeout(triggerReadyTimer);
            clearTimeout(fallbackOpenTimer);

            openPopup();

            $(document).off('keydown.awaNewsletter').on('keydown.awaNewsletter', function (event) {
                if (event.key === 'Escape' && $popup.is(':visible')) {
                    closePopup();
                    $(document).off('keydown.awaNewsletter');
                }
            });

            setTimeout(function () {
                if ($popup.is(':visible')) {
                    $popup.find('#newsletter-popup').focus();
                }
            }, focusDelay);

            if (window.console && window.console.debug) {
                window.console.debug('[AWA Newsletter] opened by:', source);
            }
        }

        function bindSmartOpenTriggers() {
            if (triggerBound || shouldAbortOpen()) {
                return;
            }

            triggerBound = true;

            $(window).on('scroll.awaNewsletterTrigger', function () {
                if (scrollTicking) {
                    return;
                }

                scrollTicking = true;
                window.requestAnimationFrame(function () {
                    var scrollState = getScrollProgress();

                    scrollTicking = false;

                    if (scrollState.top >= minScrollPx || scrollState.percent >= scrollPercent) {
                        requestOpen('scroll');
                    }
                });
            });

            if (exitIntentEnabled && window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                $(document).on('mouseout.awaNewsletterTrigger mouseleave.awaNewsletterTrigger', function (event) {
                    var relatedTarget = event.relatedTarget || event.toElement;

                    if (relatedTarget) {
                        return;
                    }

                    if (typeof event.clientY === 'number' && event.clientY <= 12) {
                        requestOpen('exit-intent');
                    }
                });
            }

            // Se o usuário já estiver abaixo da dobra quando os triggers forem armados, abre imediatamente.
            (function () {
                var scrollState = getScrollProgress();

                if (scrollState.top >= minScrollPx || scrollState.percent >= scrollPercent) {
                    requestOpen('scroll-initial');
                }
            }());
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
        ensureOverlayObserver();
        effectiveDelay = forcePreview ? 120 : popupDelay;
        triggerReadyDelay = Math.max(effectiveDelay, 4000);
        fallbackOpenDelay = Math.max(triggerReadyDelay + 14000, 20000);
        isHomePage = $('body').hasClass('cms-index-index') ||
            $('body').hasClass('cms-home') ||
            $('body').hasClass('cms-homepage_ayo_home5');

        if (!forcePreview && homeOnly && !isHomePage) {
            return;
        }

        if (!forcePreview && getCookie(cookieName) === '1') {
            return;
        }

        if (forcePreview) {
            setTimeout(function () {
                requestOpen('preview');
            }, effectiveDelay);
            return;
        }

        // Home (Ayo Home 5): comportamento mais profissional para não esconder o hero
        // imediatamente. Abre por scroll/exit-intent e mantém fallback tardio.
        if (smartTrigger && isHomePage) {
            triggerReadyTimer = setTimeout(function () {
                if (shouldAbortOpen()) {
                    return;
                }

                bindSmartOpenTriggers();
            }, triggerReadyDelay);

            fallbackOpenTimer = setTimeout(function () {
                requestOpen('fallback-timeout');
            }, fallbackOpenDelay);

            return;
        }

        setTimeout(function () {
            requestOpen('delay');
        }, effectiveDelay);
    };
});
