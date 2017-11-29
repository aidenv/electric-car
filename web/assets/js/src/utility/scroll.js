(function($) {
    'use strict';

    $.fn.isScrollVisible = function() {
        var $window = $(window);

        var docViewTop = $window.scrollTop();
        var docViewBottom = docViewTop + $window.height();

        var elemTop = this.offset().top;
        var elemBottom = elemTop + this.height();

        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    };

    $.fn.scrollTo = function(time, verticalOffset, onComplete) {
        onComplete = onComplete ? onComplete: function() {};
        time = typeof(time) != 'undefined' ? time : 1000;
        verticalOffset = typeof(verticalOffset) != 'undefined' ? verticalOffset : 0;
        var offset = this.offset();
        var offsetTop = offset.top + verticalOffset;
        $('html, body').animate({
            scrollTop: offsetTop
        }, time, 'swing', onComplete);
    };

    $.fn.stickyScroll = function(css) {
        var $element = this,
            cssPosition = $element.css('position'),
            $tagger = $('<div></div>');

        $element.before($tagger);
        css = $.extend({
            position: 'fixed',
            bottom: '0',
            left: '0',
            right: '0',
            'z-index': '1000',
            background: '#ececec'
        }, css);

        $(document).on('scroll', function() {
            if (!$tagger.isScrollVisible()) {
                $element.css(css);
            }
            else {
                $element.css('position', cssPosition);
            }
        });
    };
})(jQuery);