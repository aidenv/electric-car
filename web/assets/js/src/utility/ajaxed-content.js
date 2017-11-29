(function($) {
    'use strict';

    var cachedContents = {};

    $('[data-ajaxed-content]').each(function() {
        var $elem = $(this);
        $elem.on('mouseover', function() {
            if (!$elem.data('loaded')) {
                var containerSelector = $elem.data('ajaxed-content');
                var $container = $(containerSelector);
                var url = $elem.data('url');

                if (cachedContents.hasOwnProperty(url)) {
                    $container.html(cachedContents[url]);
                }
                else {
                    $container.yiLoader();
                    $.ajax({
                        url: url,
                        beforeSend: function() {
                            $container.trigger('loader.start');
                        },
                        success: function(html) {
                            $container.html(html);
                            cachedContents[url] = html;
                        },
                        complete: function() {
                            $container.trigger('loader.stop');
                        }
                    });
                }

                $elem.data('loaded', true);
            }
        });
    });
})(jQuery);