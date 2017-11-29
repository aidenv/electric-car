(function($) {
    var $locationSelectors = $('[data-location]');
    var url = Routing.generate('core_get_child_locations');

    // adjust visibility so that chrome
    // can target it when html5 validation occurs
    var adjustVisibility = function($locationSelector) {
        $locationSelector.attr("style", "display: inline-block !important");
        $locationSelector.css({
            position: 'absolute',
            opacity: 0,
            width: 0,
            height: 0
        });
    };

    // clears the choices on child locations
    var clearChildren = function($locationSelector) {
        var data = $locationSelector.data('location');
        if (data && data.hasOwnProperty('target')) {
            var $target = $(data.target);
            $target.empty();
            $target.dropdown('set text', $target.data('placeholder'));
            $target.dropdown('restore defaults');
            clearChildren($target);
        }
    };

    $.fn.locationSelector = function() {
        adjustVisibility(this);

        this.on('change', function(evt) {
            var $locationSelector = $(evt.target),
                data = $locationSelector.data('location'),
                $form = $locationSelector.closest('form'),
                $target = $(data.target)
            ;

            var locationId = $locationSelector.val();
            if (!(locationId > 0)) {
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    locationId: locationId,
                    locationTypeId: data.locationTypeId
                },
                beforeSend: function() {
                    $target.parent('div').addClass('loading');
                },
                success: function(data) {
                    clearChildren($locationSelector);
                    for (var locationId in data) {
                        $target.append('<option value="'+locationId+'">'+data[locationId]+'</option>');
                    }
                    $target.parent('div').removeClass('loading');
                    $target.val(null);
                }
            });
        });
    };

    $locationSelectors.each(function() {
        $(this).locationSelector();
    });
})(jQuery);