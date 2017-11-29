(function ($) {

    $(document).ready(function () {
        var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
        var $removeButton = $('#remove-brand');
        $removeButton.html('Yes').attr('disabled', false);
        var $messageModal = $('#modal-message');
        $('.brand-id:checked').removeAttr('checked');

        $(document).on('click', '.remove-brand', function () {
            var brandIds = [];
            $('.brand-id:checked').each(function() {brandIds.push($(this).val())});

            if (brandIds.length == 0) {
                $messageModal.find('.header-content').html('Please check at least one brand.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            $('#confirm-modal')
                .modal({
                    closable  : false,
                    onApprove: function () {
                        $.ajax({
                            url        : Routing.generate('cms_remove_brands'),
                            dataType   : 'json',
                            type       : 'post',
                            data       : {brandIds: brandIds},
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
