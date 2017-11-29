(function($) {
    var $body = $('body');
    var $models = $('[data-model]');
    $models.each(function() {
        var $model = $(this);
        var key = $model.data('model');
        $body.data(key, $model.val());

        $model.on('change', function() {
            $body.data(key, $model.val());            
        });
    });
})(jQuery);