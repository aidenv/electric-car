(function($) {
    'use strict';

    $.fn.eventDelay = function() {
        var
            $elem = this,
            args = Array.prototype.slice.call(arguments),
            delay = args.pop(),
            fxn = args.pop()
        ;

        if (typeof fxn == 'function') {
            var 
                timeoutFxn,
                delayFxn = function() {
                    clearTimeout(timeoutFxn);
                    timeoutFxn = setTimeout(fxn, delay);
                }
            ;

            args.push(delayFxn);
            $elem.on.apply(this, args);
        }
    };
})(jQuery);