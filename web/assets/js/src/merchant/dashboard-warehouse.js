(function($) {
    var editClicked = false,
        initForm = function(content)
        {
            if (typeof content !== 'undefined') {
                $('form[name="user_warehouse"]').replaceWith(content);
            }

            var warehouseAddressForm = $('form[name="user_warehouse"]'),
                userAddressFormFields = {};
            if (warehouseAddressForm.length > 0) {

                loadingForm(false);

                var requiredFields = warehouseAddressForm.find('[required="required"]');
                requiredFields.each(function() {
                    var fieldName = $(this).attr('name');
                    userAddressFormFields[fieldName] = {
                        identifier: fieldName,
                        rules: [{type: 'empty'}]
                    };
                });

                warehouseAddressForm.form({
                    fields: userAddressFormFields,
                    onSuccess: function(evt) {
                        evt.preventDefault();
                        submitForm($(this));
                    }
                });

                warehouseAddressForm.find('.single.selection').dropdown();
                warehouseAddressForm.find("select").removeAttr("style");
                warehouseAddressForm.find('[data-location]').locationSelector();
            }
        },
        loadingForm = function(state) {
            var warehouseAddressForm = $('form[name="user_warehouse"]');

            if (state == true) {
                warehouseAddressForm.addClass('loading');
            }
            else {
                warehouseAddressForm.removeClass('loading');
            }
        }
        requestForm = function(warehouse) {
            var warehouseId = typeof warehouse == 'undefined' ? '' :  '/' + warehouse,
                warehouseModal = $(".add-warehouse-modal"),
                warehouseModalHeader = warehouseModal.find('h4'),
                warehouseModalLabel = 'Add Warehouse';

            if (warehouse) {
                warehouseModalLabel = 'Edit Warehouse'
            }
            warehouseModalHeader.html(warehouseModalLabel);

            warehouseModal.modal("show");
            if (editClicked) {
                $.ajax({
                    method: 'GET',
                    url: Routing.generate('merchant_user_warehouse_form') + warehouseId,
                    beforeSend: function() {
                        loadingForm(true);
                    },
                    success: function(response) {
                        initForm(response);
                    },
                    error: function (request) {
                        warehouseModal.modal('hide');
                        handleRequestError(request);
                    }
                });
            }
            else {
                initForm();
            }
        },
        submitForm = function(form) {
            var submitButton = form.find('button[type="submit"]');

            $.ajax({
                method: 'POST',
                data: form.serialize(),
                url: form.attr('action'),
                beforeSend: function() {
                    loadingForm(true);
                    submitButton.prop('disabled', true);
                },
                success: function(response) {
                    if (typeof response.isSuccessful != 'undefined') {
                        showDefaultModal({
                            message: response.message
                        });
                    }
                    else {
                        submitButton.prop('disabled', false);
                        loadingForm(false);
                    }
                },
                error: function (request) {
                    submitButton.prop('disabled', false);
                    handleRequestError(request);
                }
            });
        },
        handleRequestError = function(request) {
            alert("Error: " + request.status + " occured. Please contact our IT Team.");
            loadingForm(false);
        };

    $(".add-warehouse").click(function(){
        requestForm();
    });

    $(".delete-trigger").on("click", function() {
        var self = $(this);

        showConfirmModal({
            message: 'Are you sure you want to delete this warehouse?',
            labels: {
                approve: 'Yes',
                deny: 'No'
            },
            callbacks: {
                onApprove: function() {
                    $.ajax({
                        url: Routing.generate('merchant_user_warehouse_delete') + '/' + self.data('id'),
                        beforeSend: function() {
                            loadApproveButton();
                        },
                        success: function(response) {
                            showDefaultModal({
                                message: response.message,
                                reload: response.isSuccessful
                            });
                        },
                        error: function(request) {
                            handleRequestError(request);
                        },
                        complete: function() {
                            loadApproveButton(false);
                        }
                    });

                    return false;
                }
            }
        });
    });

    $("button.edit-warehouse").on('click', function(evt) {
        var self = $(this);
        editClicked = true;
        requestForm(self.data('id'));
    });

})(jQuery);
