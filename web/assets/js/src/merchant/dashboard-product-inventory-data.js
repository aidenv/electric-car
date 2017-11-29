(function($) {

    var handleRequestError = function(request) {
        alert("Error: " + request.status + " occured. Please contact our IT Team.");
    };

    $(".import-inventory-data").click(function(){
        $(".import-inventory-data-modal").modal("show");
    });

    //Trigger sae button import
    $(".save-trigger").on("click", function(){
        showDefaultModal({
            message: "Imported data successfully saved",
        });
    });

    $(".edit-actual-inventory").on("click", function(){
        $(this).fadeOut(300, function(){
            $(this).parent().find(".save-actual-inventory").fadeIn(300);
        });

        $(this).parents("tr").find(".value").fadeOut(300, function(){
            $(this).parents("tr").find(".form-ui").fadeIn(300);
        });
    });

    $(".save-actual-inventory").on("click", function(){
        var self = $(this),
            parentRow = self.closest('tr'),
            currentValue = parentRow.find('.current-value').data('quantity'),
            quantityElement = parentRow.find('.quantity'),
            quantity = parseInt(quantityElement.val());

        if (isNaN(quantity)) {
            quantity = currentValue;
        }

        $.ajax({
            method: 'POST',
            data: {
                quantity: quantity,
                productUnit: self.data('productUnit'),
                userWarehouse: self.data('warehouse'),
            },
            url: Routing.generate('merchant_user_warehouse_inventory_update'),
            beforeSend: function() {
                self.prop('disabled', true);
            },
            success: function(response) {
                if (typeof response.isSuccessful != 'undefined') {
                    showDefaultModal({
                        message: response.message,
                        reload: false,
                    });

                    if (response.isSuccessful) {
                        parentRow.find('.quantity-value').html(quantity);
                        parentRow.find('.current-value').data('quantity', quantity);
                        quantityElement.attr('placeholder', quantity);
                    }
                }
            },
            error: function (request) {
                handleRequestError(request);
            },
            complete: function (){
                self.prop('disabled', false);
                self.fadeOut(300, function(){
                    self.parent().find(".edit-actual-inventory").fadeIn(300);
                });

                self.parents("tr").find(".form-ui").fadeOut(300, function(){
                    self.parents("tr").find(".value").fadeIn(300);
                });
            }
        });
    });
})(jQuery);

