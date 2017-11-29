(function($) {
    $dataStarRating = $('[data-star-rating]');

    $dataStarRating.each(function() {
        var $elem = $(this);
        
        $elem.__proto__.text = function(value) {
            var $elem = $(this),
                target = $elem.data('star-rating');
            target = target ? target: '.icon-star-o';
            var $targets = $elem.find(target),
                limit = parseInt(value),
                i = 0;
                
            $targets.each(function() {
                var $target = $(this);
                if (i++ < limit) {
                    $target.addClass('active');
                }
                else {
                    $target.removeClass('active');
                }
            });
        };
    });
})(jQuery);