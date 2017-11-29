(function($) {
    'use strict';

    var options = {};

    $.fn.yilinkerAutoComplete = function(settings) {
        settings = $.extend({}, options, settings);

        var $elem = $(this);
        $elem.dropdown(settings);
    };

    var $autocomplete = $('[data-autocomplete]');
    $autocomplete.each(function() {
        var $elem = $(this);
        var url = $elem.data('autocomplete');
        var connector = url.indexOf('?') < 0 ? '?q': '&q';

        var settings = {
            apiSettings: {
                beforeSend: function(settings) {
                    var url = $elem.data('autocomplete');
                    var connector = url.indexOf('?') < 0 ? '?q': '&q';
                    settings.url = url+connector+'={query}';

                    return settings;
                },
                url: url+connector+'={query}'
            }
        };

        $elem.addClass('search');
        $elem.yilinkerAutoComplete(settings);
    });

})(jQuery);