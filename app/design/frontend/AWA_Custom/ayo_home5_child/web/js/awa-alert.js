/**
 * AWA Motos — Inline Alert / Notification
 * Exibe alertas inline com auto-dismiss e tipos visuais.
 * Compatível com RequireJS.
 *
 * Uso programático:
 *   require(['AWA_Custom/js/awa-alert'], function(awaAlert) {
 *     awaAlert.show({ type: 'success', message: 'Operação concluída!', container: '#msg-area' });
 *   });
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    var ICONS = {
        info:    '&#9432;',
        success: '&#10003;',
        warning: '&#9888;',
        error:   '&#10005;'
    };

    var DEFAULTS = {
        type:       'info',
        message:    '',
        title:      '',
        container:  'body',
        duration:   5000,
        dismissible: true,
        position:   'prepend' // 'prepend' | 'append'
    };

    function buildHtml(opts) {
        var icon   = ICONS[opts.type] || ICONS.info;
        var title  = opts.title
            ? '<span class="awa-alert__title">' + escapeHtml(opts.title) + '</span>'
            : '';
        var close  = opts.dismissible
            ? '<button class="awa-alert__close" aria-label="' + $t('Fechar') + '" type="button">&#10005;</button>'
            : '';

        return '<div class="awa-alert awa-alert--' + opts.type + ' awa-alert--inline" role="alert" aria-live="polite">' +
            '<span class="awa-alert__icon" aria-hidden="true">' + icon + '</span>' +
            '<span class="awa-alert__body">' + title + escapeHtml(opts.message) + '</span>' +
            close +
            '</div>';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    var awaAlert = {
        /**
         * Exibe alerta no container especificado.
         * @param {Object} opts
         */
        show: function (opts) {
            opts = Object.assign({}, DEFAULTS, opts || {});

            var $container = $(opts.container);
            if (!$container.length) { return; }

            var $alert = $(buildHtml(opts));

            // Remove alertas inline anteriores do mesmo tipo
            $container.find('.awa-alert--inline.awa-alert--' + opts.type).remove();

            if (opts.position === 'append') {
                $container.append($alert);
            } else {
                $container.prepend($alert);
            }

            // Dismiss manual
            $alert.on('click', '.awa-alert__close', function () {
                awaAlert.hide($alert);
            });

            // Auto-dismiss
            if (opts.duration > 0 && opts.type !== 'error') {
                setTimeout(function () {
                    awaAlert.hide($alert);
                }, opts.duration);
            }

            return $alert;
        },

        /**
         * Remove alerta com fade.
         * @param {jQuery|string} $alert
         */
        hide: function ($alert) {
            $alert = $($alert);
            $alert.css({ transition: 'opacity 300ms', opacity: 0 });
            setTimeout(function () { $alert.remove(); }, 320);
        },

        /**
         * Remove todos os alertas inline de um container.
         * @param {string} container
         */
        clearAll: function (container) {
            $(container || 'body').find('.awa-alert--inline').each(function () {
                awaAlert.hide($(this));
            });
        }
    };

    return awaAlert;
});
