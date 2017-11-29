(function ($) {

    $(document).ready(function () {
        var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
        var $removeButton = $('#remove-store');
        var $messageModal = $('#modal-message');
        $removeButton.html('Yes').attr('disabled', false);
        $('.store-id:checked').removeAttr('checked');

        $(document).on('click', '.remove-store', function () {
            var storeIds = [];
            $('.store-id:checked').each(function() {storeIds.push($(this).val() + '-' + $(this).attr('data-store-list-node-id'))});

            if (storeIds.length == 0) {
                $messageModal.find('.header-content').html('Please check at least one store.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            $('#confirm-modal')
                .modal({
                    closable  : false,
                    onApprove: function () {
                        $.ajax({
                            url        : Routing.generate('cms_remove_stores'),
                            dataType   : 'json',
                            type       : 'post',
                            data       : {storeIds: storeIds},
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

    });

})(jQuery);
