(function($) {
    var $body = $('body');
    $body.on('loadAttributeChoosers', function(evt, data) {
        $attributeChooser = data.attributeChooser;
        $attributeChooser.each(function() {
            var $elem = $(this);
            // data is a list of productUnits
            var data = $elem.data('attribute-chooser'),
                $dropDowns = $elem.find('[data-dropdown]');

            if (typeof data != typeof {}) {
                return;
            }

            $dropDowns.each(function() {
                var $dropdown = $(this);
                var $options = $dropdown.find('option');
                var options = {};
                $options.each(function() {
                    var $option = $(this);
                    var attributeValue = $option.text();
                    if (typeof options[attributeValue] != typeof {} && $option.val().trim().length) {
                        options[attributeValue] = [];
                    }
                    if ($option.val().trim().length) {
                        options[attributeValue].push($option.val());
                    }
                });

                for (var attrName in options) {
                    var firstValue = options[attrName].shift();
                    var $attributeOptions = $dropdown.parent().find('[data-value]');
                    $attributeOptions.each(function() {
                        var $attributeOption = $(this);
                        var attrValue = $attributeOption.data('value');
                        if (options[attrName].indexOf(""+attrValue) > -1) {
                            $attributeOption.addClass('filtered');
                        }
                        else if (attrValue == firstValue) {
                            $attributeOption.removeClass('filtered');
                        }
                    });
                }
            });

            var hideDuplicates = function($dropdown, value) {
                var $options = $dropdown.find('option');
                var options = {};
                $options.each(function() {
                    var $option = $(this);
                    var attributeValue = $option.text();
                    if (typeof options[attributeValue] != typeof {} && $option.val().trim().length) {
                        options[attributeValue] = [];
                    }
                    if ($option.val().trim().length) {
                        options[attributeValue].push($option.val());
                    }
                });

                for (var attrName in options) {
                    if (options[attrName].indexOf(value) > -1) {
                        var firstValue = value
                    }
                    else {
                        var firstValue = options[attrName].shift();
                    }
                    var $attributeOptions = $dropdown.parent().find('[data-value]');
                    $attributeOptions.each(function() {
                        var $attributeOption = $(this);
                        var attrValue = $attributeOption.data('value');
                        if (options[attrName].indexOf(""+attrValue) > -1 && attrValue != value) {
                            $attributeOption.addClass('filtered');
                        }
                        else if (attrValue == firstValue) {
                            $attributeOption.removeClass('filtered');
                        }
                        else {
                            $attributeOption.removeClass('disabled').show();
                        }
                    });
                }
            };
    
            $elem.on('setUnit', function(evt, unitId) {
                var ctr = 0;
                var combination = data[unitId].combination;
                    $elem.data('unitId', unitId);
                $dropDowns.each(function() {
		    var $elem = $(this);
		    var value = combination[ctr++];
		    $elem.dropdown('set selected', value);
		    hideDuplicates($elem, value);
                });
            });

            var defaultUnit = $elem.data('default');
            $elem.trigger('setUnit', defaultUnit);

            $elem.on('disable', function(evt, exceptIndex) {
                $dropDowns.each(function() {
                    var index = $dropDowns.index(this);
                    var $elem = $(this);
                    for (var i in data) {
                        var value = data[i].combination[index];
                        $items = $elem.dropdown('get item', value);
                        if ($items.length && $items[0]) {
                            if (exceptIndex == index) {
                                $items[0].removeClass('disabled').show();
                            }
                            else {
                                $items[0].addClass('disabled').hide();
                            }
                        }
                    }
                });
            });

            $elem.on('enableUnit', function(evt, unitId) {
                var combination = data[unitId].combination;
                var ctr = 0;
                $dropDowns.each(function() {
                    var $elem = $(this);
                    var value = combination[ctr++];
                    $items = $elem.dropdown('get item', value);
                    if ($items.length && $items[0]) {
                        $items[0].removeClass('disabled').show();
                    }
                    else {
                        $elem.dropdown('set text', $elem.data('dropdown'));
                    }
                });
            });

            $elem.on('imageSelected', function(evt, imageId) {
                var  unitIds = [];
                for (var i in data) {
                    var productUnit = data[i];
                    if (productUnit.imageIds.indexOf(""+imageId) > -1) {
                        unitIds.push(productUnit.productUnitId);
                    }
                }

                var unitId = $elem.data('unitId');
                if (unitIds && unitIds.length && unitIds.indexOf(unitId) < 0) {
                    $elem.trigger('setUnit', unitIds.shift());
                }
            });
            
            var frozen = false;
            $dropDowns.on('change', function(evt) {
                if (frozen) return; 
                setTimeout(function() { 
                    frozen = false; 
                }, 100);
                frozen = true;

                var index = $dropDowns.index(this);
                var value = $(this).val();
                var name = $(this).text();

                $elem.trigger('disable', index);
                
                var combinationNames = [];
                $dropDowns.each(function() {
                    var $dropDown = $(this);
                    var dropdownValue = $dropDown.val();
                    var $option = $dropDown.find('option[value="'+dropdownValue+'"]');
                    combinationNames.push($option.text());
                });

                var first;
                var current;
                for (var i in data) {
                    var combinationNamesHash = JSON.stringify(combinationNames);
                    if (!current && JSON.stringify(data[i].combinationNames) == combinationNamesHash) {
                        current = i;
                    }

                    if (data[i].combination[index] == value) {
                        $elem.trigger('enableUnit', i);
                        if (!first) {
                            first = i;
                        }
                    }
                }

                if (current) {
                    $elem.trigger('setUnit', current);
                }
                else if (first) {
                    $elem.trigger('setUnit', first);
                }
            });
        });
    });

    var $attributeChooser = $('[data-attribute-chooser]');
    $body.trigger('loadAttributeChoosers', {attributeChooser: $attributeChooser});
})(jQuery)
