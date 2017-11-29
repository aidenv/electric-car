(function($) {

    var USER_TYPE_BUYER = 0;
    var USER_TYPE_SELLER = 1;
    var STORE_TYPE_MERCHANT = 0;
    var STORE_TYPE_RESELLER = 1;

    $(document).ready(function(){

        var smsCheckBoxState;
        smsCheckBoxState = $('.enable-sms-checkbox').is(':checked');
        
        $('.verify-contact-number-form').on('submit', function(event){
            event.preventDefault();
        });

        $('.disable-account-checkbox').on('change', function(){
            var $this = $(this);
            if($this.is(':checked')){
                var $passwordInput = $(".password-input");
                $('.deactivate-confirm-password-modal').modal({
                    onShow : function(){
                        $passwordInput.val('');
                    },
                    onApprove : function(){
                        var csrfToken = $("meta[name=csrf-token]").attr("content");
                        var password = $passwordInput.val();
                        var $approveButton = $(this).find('.submit-to-success');
                        var $passwordVerifyPrompt = $('.password-verify-prompt');
                        $.ajax({
                            type: "POST",
                            url: Routing.generate('core_user_disable_account'),
                            data: {
                                'password' : password,
                                '_token' : csrfToken,
                            },
                            beforeSend: function(){
                                $approveButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                            },
                            success: function(data){
                                if(data.isSuccessful){
                                    $('.success-deactivation-modal').modal('show');

                                    if(data.data.userType == USER_TYPE_SELLER && data.data.storeType == STORE_TYPE_RESELLER){
                                        setTimeout(function(){
                                            window.location.href = Routing.generate("user_affiliate_login");
                                        }, 3000);
                                    }
                                    else{
                                        setTimeout(function(){
                                            location.reload();
                                        }, 3000);
                                    }
                                }
                                else{
                                    $passwordVerifyPrompt.find('.message-box')
                                                         .html(data.message);
                                    $passwordVerifyPrompt.show();
                                }
                            },
                            complete : function(){
                                $approveButton.html("Deactivate").removeClass('disabled');
                            }
                        });
                        return false;
                    },
                    onHide : function(){
                        $this.attr('checked', false);
                    }
                }).modal('show');
            }
        });

        $('.enable-sms-checkbox').on('change', function(){
            var $this = $(this);
            var currentState = $this.is(':checked');
            $('.sms-subscription-confirm-modal').modal({
                onApprove: function(){
                    var csrfToken = $("meta[name=csrf-token]").attr("content");
                    var $approveButton = $(this).find('.submit-to-success');
                    $.ajax({
                        type: "POST",
                        url: Routing.generate('core_sms_subscription'),
                        data: {
                            '_token' : csrfToken,
                            'isSubscribe' : $this.is(':checked') ? 'true' : 'false',
                        },
                        beforeSend: function(){
                            $approveButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                        },
                        complete : function(){
                            smsCheckBoxState = currentState;
                            $approveButton.html("Continue").removeClass('disabled');
                            $this.prop('checked', currentState);
                            $('.sms-subscription-confirm-modal').modal('hide');
                        }
                    });
                    return false;
                },
                onHide : function(){
                    if(smsCheckBoxState !== currentState){
                        $this.prop('checked', smsCheckBoxState);
                    }
                }
            }).modal('show');
        });

    });

})(jQuery);
