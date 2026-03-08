/**
 * AWA Announcement Bar — fecha a barra e persiste preferência na sessão.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $bar = $(element);
        var storageKey = 'awa_announcement_closed_' + (config.version || '1');

        // Não mostra se o usuário já fechou nesta sessão
        if (sessionStorage.getItem(storageKey)) {
            $bar.remove();
            return;
        }

        $bar.find('[data-action="close-announcement"]').on('click', function () {
            $bar.slideUp(200, function () {
                $bar.remove();
            });
            try {
                sessionStorage.setItem(storageKey, '1');
            } catch (e) {
                // sessão indisponível — ignora silenciosamente
            }
        });
    };
});
