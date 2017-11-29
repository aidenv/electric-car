(function($) {

    $.fn.yiLoader = function(settings) {
        var options = {
            fixed: false
        };

        settings = $.extend({}, options, settings);

        var $elem = this;
        var $loader = $(
            '<div data-loading-dimmer class="ui '+(settings.fixed ? 'page': '')+' dimmer inverted">'+
                '<div class="ui active inverted large loader"></div>'+
            '</div>'
        );

        $elem.dimmer({
            opacity: '0.60',
            closable: false,
            template: {
                dimmer: function() {
                    return $loader;
                }
            }
        });

        $elem.on('loader.start', function(evt) {
            if ($elem.find('[data-loading-dimmer]').length < 1) {
                $elem.append($loader);
            }
            $elem.dimmer('show');
        });

        $elem.on('loader.stop', function(evt) {
            if ($elem.find('[data-loading-dimmer]').length < 1) {
                $elem.append($loader);
            }
            $elem.dimmer('hide');
        });
    };

    var $loaders = $('[data-yi-loader]');
    $loaders.each(function() {
        $(this).yiLoader();
    });

})(jQuery);