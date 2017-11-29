(function($) {
    var $currentUser = null;
    var $sendMessageForm = $("form[name='send-message']");
    
    $('.cancellation-remark').on('click', function(){
        $(this).closest('.form').removeClass('error');
    });

    $('.cancellation-orderproducts input').on('change', function(){
        $('.order-cancellation-prompt').hide();
    });

    $(".cancel-order-trigger").click(function(){
        var $orderProductInputs = $('.cancellation-orderproducts input');
        var $cancellationTextarea = $('.cancellation-remark');
        var $reasonDropdown =  $('.cancellation-reason');
        var $errorPrompt = $('.order-cancellation-prompt');
        $(".cancel-order-modal").modal({
            onShow : function(){
                $cancellationTextarea.closest('.form').removeClass('error');
                $errorPrompt.hide();
                $orderProductInputs.attr('checked', false);
                $cancellationTextarea.val('');
                $reasonDropdown.dropdown('set selected', 0);
            },
            onApprove: function(){

                var hasError = false;
                var csrfToken = $("meta[name=csrf-token]").attr("content");
                var remark = $.trim($cancellationTextarea.val());
                var reason = $.trim($reasonDropdown.find('select').val());
                var orderProducts = [];
                $.each($('.cancellation-orderproducts input:checked'), function(){
                    orderProducts.push($(this).val());
                });

                if(remark.length === 0){
                    $errorPrompt.find('.message-box').html('Please fill remark.');
                    $errorPrompt.show();
                    hasError = true;
                }

                if(orderProducts.length === 0){
                    $errorPrompt.find('.message-box').html('An item must be selected');
                    $errorPrompt.show();
                    hasError = true;
                }

                if(hasError){
                    return false;
                }

                $('.verify-cancel-modal').modal({
                    onApprove: function(){
                        var $button = $(this).find('.submit-to-cancel-success');
                        $.ajax({
                            type: "POST",
                            url: Routing.generate('transaction_cancellation'),
                            data: {
                                'orderProducts' : orderProducts,
                                'remark' : remark,
                                'reason' : reason,
                                '_token' : csrfToken,
                            },
                            beforeSend : function(){
                                $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                            },
                            success: function(data){
                                $button.html("Submit").removeClass('disabled');
                                if(data.isSuccessful === true){
                                    $.each(orderProducts, function(index, value){
                                        $('.cancellation-orderproducts :input[value="'+value+'"]')
                                            .closest('.order-product-item-container')
                                            .remove();
                                    });
                                    if($orderProductInputs.length - orderProducts.length < 1){
                                        $('.cancel-order-trigger').remove();
                                    }
                                    $('.success-cancel-modal').modal("show");
                                }
                                else{
                                    //location.reload();
                                }
                            }
                        });
                        return false;   
                    }
                }).modal('show');
            }
        }).modal("show");
    });



    $(".send-message-trigger").on("click", function(){
        var $this = $(this);

        $(".send-message-modal").modal("show");

        $currentUser = $this.attr("data-id");

        $('.coupled').modal({
            allowMultiple: false
        });
    });

    $(document).ready(function(){

        var $formRules =  {
            fields : {
                message: {
                    identifier  : 'message',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[1024]',
                            prompt : 'Please enter at most 1024 characters'
                        }
                    ]
                }
            },
            onSuccess: function(){
                var $message = $("textarea[name='message']").val().trim();
                var $submitButton = $(".send-message-modal .submit-button");
                if($message != ""){
                    $.ajax({
                        url: Routing.generate('core_send_message'),
                        type: 'POST',
                        data: {recipientId:$currentUser,message:$message,isImage:0},
                        beforeSend: function () {
                            $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").attr("disabled", true);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                $("textarea[name='message']").val("");
                                $(".send-message-modal").modal("hide");
                                $(".success-send-message").modal("show");
                            }
                        },
                        complete: function( xhr ) {
                            $submitButton.html("Submit").removeClass('disabled');
                        }
                    });
                }

                return false;
            }
        };

        $sendMessageForm.form($formRules);
    });
})(jQuery);
