(function($) {
    'use strict';

    var $ajaxPartials = $('[data-ajax-partial]');
    var addressListUrl = Routing.generate('checkout_addresses');

    $ajaxPartials.each(function() {
        var $ajaxPartial = $(this),
            ajaxPartial = $ajaxPartial.data('ajax-partial');

        var $dataFeed = $ajaxPartial.parent('[data-feed]');
        if ($dataFeed.length > 0) {
            var $current = $('<div></div>').css('height', '600px');
            $ajaxPartial.after($current);

            var partialBehaviours = function($partial) {
                $partial.find('.single.selection').dropdown();
                $partial.find('[data-location]').locationSelector();
                $partial.on('submit', function(evt) {
                    evt.preventDefault();
                    $.ajax({
                        url: $partial.attr('action'),
                        type: 'POST',
                        data: $partial.serialize(),
                        success: function(html) {
                            $current = $(html);
                            $partial.replaceWith($current);
                            partialBehaviours($current);
                            var $errorPrompts = $current.find('.form-error-prompt');
                            // if there are no errors
                            if (!$errorPrompts.length) {
                                $('.edit-address-modal').modal('hide');
                                setTimeout(function() {
                                    $('.edit-success-address-modal').modal('show');
                                }, 400);

                                $.ajax({
                                    url: addressListUrl,
                                    type: 'GET',
                                    success: function(html) {
                                        var $html = $(html);
                                        $('[data-address-list]').replaceWith($html);
                                        $html.find('.ellipsis-dropdown').dropdown();
                                        var $triggerConfirmations = $html.find('[data-trigger-confirmation]');
                                        $triggerConfirmations.each(function() {
                                            $(this).triggerConfirmation();
                                        });
                                    }
                                });
                            }
                        },
                        beforeSend: function() {
                            $ajaxPartial.trigger('loader.start');
                        },
                        complete: function() {
                            $ajaxPartial.trigger('loader.stop');
                        }
                    });
                });
            };

            $dataFeed.on('update-data', function(evt, data) {
                var editUrl = eval(ajaxPartial);

                $.ajax({
                    url: editUrl,
                    type: 'GET',
                    success: function(html) {
                        if ($current) $current.remove();
                        $current = $(html);
                        $ajaxPartial.after($current);
                        partialBehaviours($current);
                    },
                    beforeSend: function() {
                        $ajaxPartial.trigger('loader.start');
                    },
                    complete: function() {
                        $ajaxPartial.trigger('loader.stop');
                    }
                });
            });
        }
    });
})(jQuery);