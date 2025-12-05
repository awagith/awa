define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('rokanthemes.countdown', {
        options: {
            seconds: 0,
            displayFormat: "<span class='countdown-section'><span class='countdown-amount'>%%D%%</span><span class='countdown-period'> : </span></span><span class='countdown-section'><span class='countdown-amount'>%%H%%</span><span class='countdown-period'> : </span></span><span class='countdown-section'><span class='countdown-amount'>%%M%%</span><span class='countdown-period'> : </span></span><span class='countdown-section'><span class='countdown-amount'>%%S%%</span><span class='countdown-period'></span></span>",
            finishMessage: ""
        },

        _create: function () {
            this._startCountdown();
        },

        _startCountdown: function () {
            var self = this;
            if (this.options.seconds < 0) {
                this.element.html(this.options.finishMessage);
                return;
            }
            this._updateDisplay();
            setInterval(function () {
                self.options.seconds--;
                if (self.options.seconds < 0) {
                    self.element.html(self.options.finishMessage);
                } else {
                    self._updateDisplay();
                }
            }, 1000);
        },

        _updateDisplay: function () {
            var secs = this.options.seconds;
            var displayStr = this.options.displayFormat;
            displayStr = displayStr.replace(/%%D%%/g, this._calcage(secs, 86400, 100000));
            displayStr = displayStr.replace(/%%H%%/g, this._calcage(secs, 3600, 24));
            displayStr = displayStr.replace(/%%M%%/g, this._calcage(secs, 60, 60));
            displayStr = displayStr.replace(/%%S%%/g, this._calcage(secs, 1, 60));
            this.element.html(displayStr);
        },

        _calcage: function (secs, num1, num2) {
            var s = ((Math.floor(secs / num1) % num2)).toString();
            if (s.length < 2)
                s = "0" + s;
            return "<b>" + s + "</b>";
        }
    });

    return $.rokanthemes.countdown;
});
