(function($) {
    'use strict';

    var $inputs = $('input, select');

    $inputs.each(function() {
        var $elem = $(this);
        $elem.on('floating.error', function(evt, message) {
            var $elem = $(this);
            $elem.trigger('floating.error-clear');
            if ($elem.is('select')) {
                $elem.one('change', function() {
                    $(this).trigger('floating.error-clear') ;
                });
                $elem = $elem.parent('div.dropdown');
            }
            var $error = $('<div class="form-error-prompt">'+message+'</div>');
            $elem.after($error);
        });

        $elem.on('floating.error-clear', function() {
            var $elem = $(this);
            if ($elem.is('select')) {
                $elem = $elem.parent('div.dropdown');
            }
            $elem.nextAll('.form-error-prompt').remove();
        });
    });

})(jQuery);