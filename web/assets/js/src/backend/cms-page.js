(function ($) {
    'use strict';

    $(document).ready(function () {
        var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
        var $removeButton = $('#remove-product');
        var $messageModal = $('#modal-message');
        $removeButton.html('Yes').attr('disabled', false);
        $('.product-id:checked').removeAttr('checked');

        $(document).on('click', '.remove-product', function () {
            var productIds = [];
            $('.product-id:checked').each(function() {productIds.push($(this).val())});

            if (productIds.length == 0) {
                $messageModal.find('.header-content').html('Please check at least one product.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            $('#confirm-modal')
                .modal({
                    closable  : false,
                    onApprove: function () {
                        $.ajax({
                            url        : Routing.generate('cms_remove_products'),
                            dataType   : 'json',
                            type       : 'post',
                            data       : {productIds: productIds},
                            beforeSend : function () {
                                $removeButton.html($loader).attr('disabled', true);
                            },
                            success    : function (isSuccessful) {

                                if (isSuccessful) {
                                    $messageModal.find('.header-content').html('Successfully removed!');
                                    $messageModal.find('.sub-header-content').html('');
                                    $messageModal
                                        .modal({
                                            closable  : false,
                                            onApprove : function () {
                                                location.reload();
                                            }
                                        })
                                        .modal('show');
                                }
                                else {
                                    $messageModal.find('.header-content').html('Server Error, try again later');
                                    $messageModal.find('.sub-header-content').html('');
                                    $messageModal.modal('show');
                                }

                            }
                        });
                    }
                })
                .modal('show');
        });

        $('[data-product-detail-link]').on('click', function(evt) {
            evt.stopPropagation();
        });

    });

})(jQuery);
