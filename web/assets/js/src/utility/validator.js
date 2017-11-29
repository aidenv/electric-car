(function($) {
    'use strict';

    $.fn.ajaxValidate = function(settings) {
        var options = {
            delay: 600
        };

        settings = $.extend({}, options, settings);
        var $elem = this;

        $elem.eventDelay('keyup', function() {
            var value = $elem.val();
            if (value) {
                var url = settings.url.replace('{value}', value);

                $.ajax({
                    url: url,
                    success: function(data) {
                        if (data.isSuccessful) {
                            $elem.trigger('ajax.validation.success', data);
                        }
                        else {
                            $elem.trigger('ajax.validation.error', data);
                        }
                    }
                });
            }
            else {
                $elem.trigger('ajax.validation.empty');
            }
        }, settings.delay);
    };

    var $ajaxValidations = $('[data-ajax-validate]');
    $ajaxValidations.each(function() {
        var $ajaxValidation = $(this);
        var settings = $ajaxValidation.data('ajax-validate');
        settings = settings ? settings: {};

        $ajaxValidation.ajaxValidate(settings);
    });

})(jQuery);