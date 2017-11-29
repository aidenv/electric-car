(function($) {
    'use strict';

    var $modal = $('[data-import-inventory-modal]');
    var importUrl = $modal.data('import-inventory-modal');
    var $header = $modal.find('[data-header]');
    var $importBtn = $('[data-import-inventory]');
    var $list = $('[data-unit-warehoust-list]');
    var $error = $modal.find('[data-error]');

    var updateData = {};
    $importBtn.on('change', function(evt) {
        var f = evt.target.files[0];

        if (f) {
            $list.empty();
            var r = new FileReader();
            r.onload = function(e) {
                var contents = e.target.result;
                var lines = contents.split('\n');
                lines.shift();

                lines.forEach(function(line) {
                    var parts = line.split(',');
                    if (parts.length != 4) {
                        return;
                    }
                    var sku = parts.shift();
                    var productName = parts.shift();
                    var systemInventory = parts.shift();
                    var actualInventory = parts.shift();

                    if (systemInventory != actualInventory) {
                        updateData[sku] = actualInventory;
                    }
                    
                    var $line = $(
                        '<tr>'+
                           '<td align="center">'+
                                sku+
                           '</td>'+
                           '<td>'+
                                productName+
                           '</td>'+
                           '<td align="center">'+
                                systemInventory+
                           '</td>'+
                           '<td align="center">'+
                                '<input data-sku="'+sku+'" data-system-invetory="'+systemInventory+'" value="'+actualInventory+'"/>'+
                           '</td>'+
                       '</tr>'
                    );
                    $header.show();
                    $list.append($line);
                });
                $modal.modal('refresh');
            };

            r.readAsText(f);
        }
    });

    $list.on('change', '[data-sku]', function() {
        var $elem = $(this);
        var sku = $elem.data('sku');
        var systemInventory = $elem.data('system-invetory');
        var actualInventory = $elem.val();

        if (systemInventory != actualInventory) {
            updateData[sku] = actualInventory;
        }
        else {
            delete updateData[sku];
        }
    });

    var $saveBtn = $modal.find('[data-save]');
    $saveBtn.on('click', function() {
        $error.hide();
        $.ajax({
            method: 'post',
            url: importUrl,
            data: {updateData: updateData},
            beforeSend: function() {
                $saveBtn.trigger('loader.start');
            },
            success: function() {
                showDefaultModal({
                    message: 'Successfully updated inventory'
                });
            },
            error: function() {
                $error.text('Something went wrong').show();
            },
            complete: function() {
                $saveBtn.trigger('loader.stop');
            }
        });
    });

})(jQuery);