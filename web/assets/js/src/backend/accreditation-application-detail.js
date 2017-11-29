(function ($) {

    $(document).ready(function () {
        var accreditationApplicationId = $('#accreditation-application-id').val();
        var accreditationApplicationType = $('#accreditation-application-type-id').val();
        var $messageContainer = $('#modal-message-container');
        var canUpdateAccreditationType = parseInt($('#application-can-update').val());

        $('#drop-down-accreditation-type').dropdown('set selected', accreditationApplicationType);
        $('.text-area-message').val('');

        $(document).on('click', '.btn-submit-remarks', function () {
            var $messageContainer = $('#modal-message-container');
            var $this = $(this);
            var applicationRemarkTypeId = $this.attr('data-id');
            var message = $this.parent().find('.text-area-message').val();

            if (message.trim() === '') {
                $messageContainer.find('.header-content').html('Add message to proceed');
                $messageContainer.find('.detail-content').html('');
                $messageContainer.modal('show');
                return false;
            }
            else if (parseInt(accreditationApplicationId) === 0 || parseInt(applicationRemarkTypeId) === 0) {
                $messageContainer.find('.header-content').html('Invalid Application, try refreshing the page.');
                $messageContainer.find('.detail-content').html('');
                $messageContainer.modal('show');
                return false;
            }

            $.ajax({
                url: Routing.generate('admin_accreditation_submit_remark'),
                method: 'post',
                dataType: 'json',
                data: {
                    accreditationApplicationId: accreditationApplicationId,
                    applicationRemarkTypeId: applicationRemarkTypeId,
                    message: message
                },
                beforeSend: function () {
                    $this.find('.text').addClass('hidden');
                    $this.find('.loader').removeClass('hidden');
                    $this.attr('disabled', true);
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find('.text').removeClass('hidden');
                    $this.find('.loader').addClass('hidden');

                    if (response.isSuccessful == true) {
                        $messageContainer.find('.header-content').html('Remark successfully sent.');
                        $messageContainer.find('.detail-content').html('');
                        $messageContainer.modal('show');
                        location.reload();
                    }
                    else {
                        $messageContainer.find('.header-content').html(response.message);
                        $messageContainer.find('.detail-content').html('');
                        $messageContainer.modal('show');
                    }

                }
            })
        });

        $(document).on('click', '#btn-update-accreditation-type', function () {
            var $this = $(this);
            var accreditationApplicationTypeId = $('#drop-down-accreditation-type').val();

            if (parseInt(accreditationApplicationId) === 0) {
                $messageContainer.find('.header-content').html('Invalid Application, try refreshing the page.');
                $messageContainer.find('.detail-content').html('');
                $messageContainer.modal('show');
                return false;
            }

            if (canUpdateAccreditationType === 0) {
                $messageContainer.find('.header-content').html('Seller cannot be Accredited, Bank Information is required.');
                $messageContainer.find('.detail-content').html('');
                $messageContainer.modal('show');
                return false;
            }

            $.ajax({
                url: Routing.generate('admin_accreditation_update_type'),
                method: 'post',
                dataType: 'json',
                data: {
                    accreditationApplicationId: accreditationApplicationId,
                    accreditationApplicationTypeId: accreditationApplicationTypeId
                },
                beforeSend: function () {
                    $this.find('.text').addClass('hidden');
                    $this.find('.loader').removeClass('hidden');
                    $this.attr('disabled', true);
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find('.text').removeClass('hidden');
                    $this.find('.loader').addClass('hidden');

                    if (response.isSuccessful == true) {
                        $messageContainer.find('.header-content').html('Successfully Updated.');
                        $messageContainer.find('.detail-content').html('');
                        $messageContainer.modal('show');
                        location.reload();
                    }
                    else {
                        $messageContainer.find('.header-content').html(response.message);
                        $messageContainer.find('.detail-content').html('');
                        $messageContainer.modal('show');
                    }

                }
            });

        });

        $(document).on('click', '.btn-submit-complete', function () {
            var $this = $(this);
            var isComplete = $this.attr('data-value');
            var remarkTypeId = $this.attr('data-id');

            if (parseInt(accreditationApplicationId) === 0) {
                $messageContainer.find('.header-content').html('Invalid Application, try refreshing the page.');
                $messageContainer.find('.detail-content').html('');
                $messageContainer.modal('show');
                return false;
            }

            $.ajax({
                url: Routing.generate('admin_accreditation_update_status'),
                method: 'post',
                dataType: 'json',
                data: {
                    isComplete: isComplete,
                    remarkTypeId: remarkTypeId,
                    accreditationApplicationId: accreditationApplicationId
                },
                beforeSend: function () {
                    $this.find('.text').addClass('hidden');
                    $this.find('.loader').removeClass('hidden');
                    $this.attr('disabled', true);
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find('.text').removeClass('hidden');
                    $this.find('.loader').addClass('hidden');

                    if (response) {

                        if (parseInt(isComplete) === 1) {
                            $this.attr('data-value', 0);
                            $this.find('.text').html('Mark as Incomplete');
                        }
                        else {
                            $this.attr('data-value', 1);
                            $this.find('.text').html('Mark as Complete');
                        }

                    }

                }
            })
        });

    });

})(jQuery);
