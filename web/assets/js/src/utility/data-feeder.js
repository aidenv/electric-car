(function($) {
    var $dataFeed = $('[data-feed]');
    var dataFeedLogic = function() {
        var $elem = $(this);
        var target = $elem.data('feed');
        $('body').on('click change', target, function() {
            var $currentTarget = $(this);
            if ($currentTarget.is(target)) {
                var data = $currentTarget.data('feeder');
                if (typeof data == typeof '') {
                    data = data.
                        replace(/\&gt\;[\s\r\n]*/g, '&gt;').
                        replace(/[\s\r\n]*\&lt\;/g, '&lt;').
                        replace(/\r?\n/g, '<br>')
                    ;

                    try {
                        data = JSON.parse(data);
                    }
                    catch(err) {
                        if (typeof data == typeof '') {
                            var e = document.createElement('div');
                            e.innerHTML = data;
                            data = e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
                            data = data.
                                replace("\\", "\\\\").
                                replace(/\=\"/g, '=\\"').
                                replace(/\"\>/g, '\\">').
                                replace(/\"\s/g, '\\" ').
                                replace(/\r?\n/g, '<br>')
                            ;
                            data = JSON.parse(data);
                        }
                    }

                }
                $elem.trigger('update-data', data);
            }
        });

        $elem.on('update-data', function(evt, data) {
            evt.stopPropagation();
            $elem.data('data', data);

            var $dataFed = $elem.find('[data-fed]');
            $dataFed.each(function() {
                var $currentDataFed = $(this),
                    dataString = $currentDataFed.data('fed'),
                    dataValue = data ? eval(dataString): '';
                if ($currentDataFed.is('input, select, textarea')) {
                    $currentDataFed.val(dataValue);
                }
                else if ($currentDataFed.is('[data-star-rating]')) {
                    $currentDataFed.text(dataValue);
                }
                else {
                    var dirty = dataValue && dataValue.search('&lt;') > -1;
                    if (dirty) {
                        var dataValue = $('<div/>').html(dataValue).text();
                    }
                    $currentDataFed.html(dataValue);
                }
            });

            var $dataFedAttributes = $elem.find('[data-fed-attributes]');
            $dataFedAttributes.each(function() {
                var $dataFedAttribute = $(this),
                    attributes = $dataFedAttribute.data('fed-attributes');

                if (typeof attributes != typeof {}) {
                    attributes = eval(attributes);
                }
                for (var attrName in attributes) {
                    var attrValue = '';
                    try {
                        attrValue = eval(attributes[attrName]);
                    } catch(e) {
                        attrValue = attributes[attrName];
                    }

                    $dataFedAttribute.attr(attrName, attrValue);
                }
            });

            $elem.trigger('compile');
        });

        $elem.on('update-visibility', function(evt) {
            evt.stopPropagation();
            var data = $elem.data('data');
            var $fedShow = $elem.find('[data-fed-show]');
            $fedShow.each(function() {
                var value = $(this).data('fed-show');
                if (eval(value)) {
                    $(this).show();
                }
                else {
                    $(this).hide();
                }
            });
        });

        $elem.on('update-loopers', function(evt) {
            evt.stopPropagation();
            var data = $elem.data('data');
            var $fedLoop = $elem.find('[data-fed-loop]');
            $fedLoop.each(function() {

                var $current = $(this),
                    value = $current.data('fed-loop'),
                    loopData = eval(value),
                    $previous = $current;
                setTimeout(function() {
                    $current.hide();
                    $current.siblings('[data-fed-cloned]').remove();
                    
                    for (var i in loopData) {
                        var $clone = $current.clone();
                        $clone.show();
                        $clone.insertAfter($previous);
                        $previous = $clone;
                        $clone.removeAttr('data-fed-loop');
                        $clone.attr('data-fed-cloned', '');
                        dataFeedLogic.call($clone[0]);
                        $clone.trigger('update-data', loopData[i]);
                    };

                }, 1);
            });
        });

        $elem.on('update-readonly', function(evt) {
            evt.stopPropagation();
            var data = $elem.data('data');
            var $fedReadonly = $elem.find('[data-fed-readonly]');
            $fedReadonly.each(function() {
                var $current = $(this);
                var value = $current.data('fed-readonly');
                if (eval(value)) {
                    $current.prop('readonly', true);
                }
                else {
                    $current.prop('readonly', false);
                }
            });
        });

        $elem.on('compile', function(evt) {
            evt.stopPropagation();
            $elem.trigger('update-visibility');
            $elem.trigger('update-readonly');
            $elem.trigger('update-loopers');
        });
    };

    $dataFeed.each(dataFeedLogic);
})(jQuery);
