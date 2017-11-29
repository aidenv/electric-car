(function($) {
    var $triggerConfirmations = $('[data-trigger-confirmation]');

    $.fn.triggerConfirmation = function() {
        var $triggerConfirmation = this;

        var preventDefault = function(evt) {
            evt.preventDefault();
        };

        $triggerConfirmation.on('click', preventDefault);
        var target = $triggerConfirmation.data('trigger-confirmation');
        var $target = $(target);
        $target.on('click', function() {
            var href = $triggerConfirmation.data('href');
            if (href) {
                window.location = href;
            }
            else {
                $triggerConfirmation.off('click', preventDefault);
                $triggerConfirmation[0].click();
            }
        });
    };

    $triggerConfirmations.each(function() {
        $(this).triggerConfirmation();
    });
    
})(jQuery);

