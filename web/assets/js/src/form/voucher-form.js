(function($) {
    'use strict';

    var dateTimePicker = function($elems) {
        $elems.each(function() {
            var $elem = $(this);
            $elem.attr('type', 'text').datetimepicker({
                format: "MM/DD/YYYY hh:mm:ss A"
            });
            var date = $elem.data('value');
            if (date) {
                $elem.data('DateTimePicker').date(date);
            }
        });
    };
    dateTimePicker($('[data-voucher-start-date], [data-voucher-end-date]'));


    $('[data-create-voucher]').click(function(){
        $('.modal-voucher-one').modal('show').modal({ blurring: true });
    });

    $('[data-voucher-modal-submit]').on('click', function() {
        $('#voucher-form').submit();
    });


    $('body').on('click', '[data-voucher-status]', function(evt) {
        var $voucherStatus = $('#voucher_isActive');
        $('[data-voucher-status]').toggle();
        var checked = $voucherStatus.prop('checked');
        $voucherStatus.prop('checked', !checked);
    });

    var
        $voucherForm = $('[data-voucher-form]'),
        editUrl = Routing.generate('admin_voucher_edit'),
        voucherId = 0
    ;

    $voucherForm.on('submit', '#voucher-form', function(evt) {
        evt.preventDefault();
        var $form = $(this);
        var params = voucherId ? '?id='+voucherId: '';

        $.ajax({
            url: editUrl+params,
            method: 'POST',
            data: $form.serialize(),
            success: function(html) {
                var $html = $(html);
                var $errors = $html.find('.form-ui-note.error ul li');
                var $content = $html.find('.content');
                $voucherForm.find('.content').replaceWith($content);
                dateTimePicker($('[data-voucher-start-date], [data-voucher-end-date]'));
                if ($errors.length < 1) {
                    location.reload();
                }
            }
        });
    });

    var refreshTab = function() {
        $('.menu [data-tab]').each(function() {
            $(this).tab();
        });
        $('[data-detail-tab-header]').click();
    };

    var refreshJSInit = function($content) {
        var $dateTimePicker = $content.find('[data-voucher-start-date], [data-voucher-end-date]');
        dateTimePicker($dateTimePicker);
        $content.find('.single.selection').dropdown();
        $(".multiple.search.selection").dropdown({
            allowAdditions: false
        });
    };

    var $origVoucherContent = $voucherForm.find('.content');
    var $voucherRows = $('[data-voucher]');
    var $voucherMenu = $('[data-voucher-tab-menu]');
    $voucherMenu.hide();

    $voucherRows.each(function() {
        var $voucherRow = $(this);
        $voucherRow.on('click', function() {
            voucherId = $voucherRow.data('voucher');
            $voucherForm.modal('setting', 'onHidden', function() {
                $voucherForm.find('.content').replaceWith($origVoucherContent);
                $voucherForm.modal('setting', 'onShow', function() {});
                voucherId = 0;
                refreshTab();
                $voucherMenu.hide();
                refreshJSInit($origVoucherContent);
            });

            $voucherForm.modal('setting', 'onShow', function() {
                $voucherMenu.show();
                $.ajax({
                    url: editUrl+'?id='+voucherId,
                    success: function(html) {
                        var $html = $(html);
                        var $content = $html.find('.content');
                        $voucherForm.find('.content').replaceWith($content);
                        refreshJSInit($content);

                        refreshTab();
                    }
                });
            });

            $voucherForm.modal('show');
        });
    });

    var generateCodeURL = Routing.generate('admin_generate_code');

    $('body').on('click', '[data-generate-voucher-code]', function(evt) {
        evt.preventDefault();
        var quantity = $('[name="voucher[quantity]"]').val();
        var $batchUpload = $('[name="voucher[batchUpload]"]');
        if ($batchUpload.prop('checked')) {
            var url = generateCodeURL+'?quantity='+quantity;
        }
        else {
            url = generateCodeURL;
        }

        $.ajax({
            url: url,
            success: function(data) {
                var value = data.codes.join(', ');
                $('#voucher_code').val(value);
            }
        });
    });

     //Multiple selection select box with tokens
    $(".multiple.search.selection").dropdown({
        allowAdditions: false
    });
})(jQuery);