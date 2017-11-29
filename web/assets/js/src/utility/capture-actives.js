(function($) {
    var $starCapture = $('[data-capture-actives]');

    $starCapture.each(function() {
        var $elem = $(this);
        var target = $elem.data('capture-actives');
        var $target = $(target);

        $target.on('click', function() {
            var $activeElems = $target.find('.active');
            if ($elem.is('input')) {
                $elem.val($activeElems.length);
            }
            else {
                $elem.text($activeElems.length);
            }
        });
    });

})(jQuery);