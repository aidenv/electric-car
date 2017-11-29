(function($) {
    'use strict';

    $('.edit-mobile-phone-number-trigger, .verify-contact-number-trigger').on('click', function(evt) {
        evt.preventDefault();
    });
    $('.verify-contact-number-form').on('submit', function(evt) {
        evt.preventDefault();
    });

    var codeSent = false;

    var openContactNumberVerificationModal = function(endCountDownInMillis)
    {
        var csrftoken = $("meta[name=csrf-token]").attr("content");
        var currentDate = new Date();
        var endDate = new Date(currentDate.getTime() + endCountDownInMillis);
        $('.time-limit').countdown(endDate, function(event) {
            codeSent = true
            $(this).text(
                event.strftime('%M:%S')
            );
        }).on('finish.countdown', function(event) {
            codeSent = false;
            $('.verify-contact-number-modal').modal('hide');
        });

        var $verificationCodeInput = $('.verification-code-input');
        var $errorPrompt = $('.contact-verify-prompt');
        $('.verify-contact-number-modal').modal({
            onShow : function(){
                $verificationCodeInput.val('');
                $errorPrompt.hide();
            },
            onApprove : function(){
                var $submitButton = $('.verify-contact-number-modal .approve');
                var verificationCode = $verificationCodeInput.val();
                if(verificationCode === ""){
                    $verificationCodeInput.parent('div').addClass('error');
                    return false;
                }
                
                $.ajax({
                    type: "POST",
                    url: Routing.generate('api_checkout_verify_mobile_code'),
                    data: {
                        'code': verificationCode,
                        'csrftoken' : csrftoken
                    },
                    beforeSend : function(){
                        $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                    },
                    success : function(data) {        
                        $(".verified-mobile-number").removeClass("hidden");
                        $(".unverified-mobile-number").addClass("hidden");
                        $('.success-change-contact-number-modal').modal("show");
                        $('[data-resend-verification]').hide();
                        $mobileVerification = $('[data-mobile-verification]');
                        if ($mobileVerification.length) {
                            $mobileVerification.removeClass('error');
                            $mobileVerification.trigger('mobile.verification.success');
                        }
                    },
                    error: function(data) {
                        $verificationCodeInput.val('');
                        $errorPrompt.show();
                    },
                    complete: function( xhr ) {
                        $submitButton.html("Submit").removeClass('disabled');
                    }
                });
                
                return false;
            }
        }).modal('show');

    };

    $('.change-contactnumber-form').on('submit', function(evt) {
        evt.preventDefault();
        var 
            $elem = $(this),
            $errorPrompt = $elem.find('.contact-number-prompt'),
            newContactNumber = $elem.find('[name="new-contact-number"]').val(),
            csrftoken = $("meta[name=csrf-token]").attr("content"),
            url = Routing.generate('api_checkout_update_contact_number')
        ;

        $.ajax({
            type: "POST",
            url: url,
            data: {
                'newContactNumber': newContactNumber,
                'csrftoken' : csrftoken
            },
            beforeSend : function(){
                $elem.find('.submit-to-verify').html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
            },
            success : function(data) {
                if(data.isSuccessful){
                    var expirationInMillis = data.data.expiration_in_minutes * 60 * 1000;
                    $(".verified-mobile-number").addClass("hidden");
                    $(".unverified-mobile-number").removeClass("hidden");
                    $mobileVerification = $('[data-mobile-verification]');
                    if ($mobileVerification.length) {
                        $mobileVerification.addClass('error');
                        $mobileVerification.trigger('mobile.verification.error');
                    }
                    openContactNumberVerificationModal(expirationInMillis);
                    // $('.verify-contact-number-modal').modal('show');
                    $('.profile-input-contactnumber').val(newContactNumber);
                    $('[data-resend-verification]').show();
                }
                else{
                    $errorPrompt.find('.message-box').html(data.message);
                    $errorPrompt.show().delay(3000);
                }
            },
            complete: function( xhr ) {
                $elem.find('.submit-to-verify').html("Submit").removeClass('disabled');
            }
        });
    });
    
    var $ajaxLoading = false;
    $('.verify-contact-number-trigger').click(function(){
        if(!$ajaxLoading && !codeSent){
            $ajaxLoading = true;

            $.ajax({
                url: Routing.generate('api_checkout_resend_verification_code'),
                success: function(response) {
                    if(response.isSuccessful){
                        var expirationInMillis = response.data.expiration_in_minutes * 60 * 1000;
                        $(".success-resend-contact-verification").modal("show");
                        openContactNumberVerificationModal(expirationInMillis);
                    }
                    $ajaxLoading = false;       
                },
                error: function(response){
                    $ajaxLoading = false;
                }
            });
        }
    });
})(jQuery);