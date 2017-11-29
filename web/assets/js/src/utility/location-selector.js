(function($) {
    'use strict';

    var locationSelector = {
        url: Routing.generate('core_get_child_locations'),
        htmlStack: [],
        queriedStack: [],
        loadNextLocation: function(html) {
            var settings = this;

            var getLocations = function(locationId) {
                var locationQuery = settings.locationQueue.shift();

                if (locationQuery) {
                    settings.queriedStack.push(locationQuery);
                    locationQuery.view = settings.view;
                    if (locationId) locationQuery.locationId = locationId;

                    $.ajax({
                        beforeSend: function() {
                            settings.element.trigger('loader.start');
                        },
                        url: settings.url,
                        type: 'POST',
                        data: locationQuery,
                        success: function(html) {
                            settings.loadNextLocation(html);
                        },
                        complete: function() {
                            settings.element.trigger('loader.stop');
                        }
                    });
                }
                else {
                    settings.element.val(locationId);
                }
            };

            if (html) {
                var $html = $(html);
                var htmlIndex = settings.htmlStack.length;
                $html.on('change', 'select', function(evt) {
                    while (settings.htmlStack.length > htmlIndex + 1) {
                        settings.htmlStack.pop().remove();
                        var returnLocationQueue = settings.queriedStack.pop();
                        settings.locationQueue.unshift(returnLocationQueue);
                    }

                    var locationId = $(this).val();
                    getLocations(locationId);
                    settings.toggleRequiredError($html);
                });
                var $lastHtml = settings.htmlStack.pop();
                if ($lastHtml) {
                    $lastHtml.after($html);
                    settings.htmlStack.push($lastHtml);
                }
                else {
                    settings.element.after($html);
                }
                if (typeof $.fn.dropdown == 'function') {
                    $html.find('[data-location-selector-part]').dropdown();
                }
                settings.htmlStack.push($html);
            }
            else {
                getLocations();
            }
        },
        toggleRequiredError: function($container) {
            var hasError = false;
            var $field = $container.find('[data-location-selector-part]');
            var fieldValue = $field.val();
            if (!fieldValue) {
                hasError = true;
                $container.addClass('error');
                $container.css({
                    'color': '#9f3a38'
                });
                $field.css({
                    'border-color': '#e0b4b4',
                    'background': '#fff6f6',
                    'color': '#9f3a38'
                });
            }
            else {
                $container.removeClass('error');
                $container.css({});
                $field.css({});
            }

            return hasError;
        },
        requiredError: function() {
            var settings = this;

            var $form = settings.element.closest('form');
            $form.on('submit', function(evt) {
                evt.preventDefault();
                
                var hasError = false;
                $.each(settings.htmlStack, function(i, $container) {
                    if (settings.toggleRequiredError($container)) {
                        hasError = true;
                    }
                });

                if (!hasError && settings.element.val()) $form[0].submit();
            });
        }
    };

    $.fn.locationSelector = function(settings) {
        locationSelector.element = this;
        var settings = $.extend({}, locationSelector, settings);
        settings.loadNextLocation();
        settings.requiredError();
    };

})(jQuery);