(function($) {

    $(document).ready(function(){

        $("input[type='checkbox'].pickup-schedule-time").change(function(){
            var $this = $(this);
            if($this.is(':checked')){
                $("input[type='checkbox'].pickup-schedule-time").not($this).removeAttr("checked")
            } 
        });

        $('.schedule-pickup-datepicker').datepicker();
        
        $('.schedule-pickup-modal-trigger').on('click', function(){

            var $pickupModalTrigger = $(this);

            $('.schedule-pickup-modal').modal({
                onApprove : function(){

                    var $this = $(this);
                    var $submitButton = $this.find('.approve');
                    var csrfToken = $("meta[name=csrf-token]").attr("content");
                    var selectedDate = $('.schedule-pickup-datepicker').data('datepicker');
                    var $errorContainer = $this.find('.pickup-schedule-prompt');
                    var remark = $('.pickup-remark').val().trim();
                    var orderProductIds = [];
                    var hasError = false;
                    var errorMessage = "";

                    $this.find('input.orderproduct-pickup:checked').each(function(){
                        orderProductIds.push($(this).data('orderproductid'));
                    });
                    var selectedTime = false;
                    $("input[type='checkbox'].pickup-schedule-time:checked").each(function(){
                        selectedTime = $(this).data('time');
                        return false;
                    });


                    if(orderProductIds.length === 0){
                        errorMessage = "Please select an item to be picked up";
                        hasError = true;
                    }
                    
                    if(false && selectedDate.viewDate < new Date()){
                        errorMessage = "Pickup date cannot be less than current date";
                        hasError = true;
                    }

                    if(false && selectedTime === false){
                        errorMessage = "Please select a time schedule";
                        hasError = true;
                    }

                    if(hasError){
                        $errorContainer.find('.message-box').html(errorMessage);
                        $errorContainer.show().delay(3000).fadeOut();
                        return false;
                    }

                    $.ajax({
                        type: "POST",
                        url: Routing.generate('dashboard_schedule_pickup'),
                        data: {
                            'datetime' : selectedDate.getFormattedDate('yyyy-mm-dd')+' '+selectedTime,
                            'orderProductIds' : orderProductIds,
                            'remark' : remark,
                            '_token' : csrfToken,
                        },
                        beforeSend : function(){
                            $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                        },
                        success : function(data){
                            if(data.isSuccessful){
                                var $orderId = $('[data-orderid]');
                                var orderId = $orderId.data('orderid');

                                $.ajax({
                                    url: Routing.generate('core_delivery_log')+'?order='+orderId,
                                    success: function(html) {
                                        var $html = $(html);
                                        $('#deliver-log-container').replaceWith(
                                             $html.find('#deliver-log-container')
                                        );
                                    }
                                });

                                $.each(orderProductIds, function(key, value){
                                    $('.pickup-orderproduct-item[data-orderproductid="'+value+'"]').remove();
                                });
                                if($('.pickup-orderproduct-item').length === 0){
                                    $pickupModalTrigger.remove();
                                }
                                var $successModal = $('.success-schedule-pickup-message');
                                waybillNumberString = "";
                                $.each(data.data, function(index, value){
                                    waybillNumberString = waybillNumberString + "," + value.waybillNumber;
                                });
                                if(waybillNumberString.charAt(0) == ','){
                                    waybillNumberString = waybillNumberString.substring(1);
                                }
                                $successModal.find('.waybill-number').html(waybillNumberString);
                                $successModal.modal('show');

                            }
                            else{
                                $errorContainer.find('.message-box').html(data.message);
                                $errorContainer.show().delay(3000).fadeOut();
                            }
                        },
                        complete : function(){
                            $submitButton.html("Submit").removeClass('disabled');
                        }
                    });
                    
                    return false;
                }
            }).modal('show');
        });
    });

})(jQuery);
