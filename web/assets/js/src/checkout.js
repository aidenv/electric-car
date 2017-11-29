(function ($) {
    var $signInError = $('#sign-in-checkout .message-box.red');
    if ($signInError.length) {
        $('[data-href="sign-in-checkout"]').addClass('active');
        $('[data-href="guest-checkout"]').removeClass('active');
        $('#sign-in-checkout').show();
    }
    else {
        //Tab functions for guest and member checkout
        $('[data-href="guest-checkout"]').addClass('active');
        $('#guest-checkout').show();
    }

    $('.prevent-default').on('click', function(evt) {
        evt.preventDefault();
    });

    $(".user-type-checkout-tab a").click(function(){
        if (!$(this).hasClass("active")) {
            var $this = $(this);
            var tabLink = $this.attr('data-href');
            var tabName = $(document).find("#"+tabLink);

            $('.tab-item-container').animate({opacity:0},function(){
                $(".user-type-checkout-tab a").not($this).removeClass("active");
                $this.addClass("active");
                $(".tab-item-container").not(tabName).hide();
                tabName.show();
                $(this).stop().animate({opacity: "1"},"slow");
            });
        }
    });

    //Display use points
    $(".use-points-trigger").click(function(){
        $(".use-points-segment").slideDown();
    });

    //Mode of payment selection
    $(".payment-option-segment").click(function(){
        var paymentCheckbox = $(this).find("input[type='checkbox']");
        $(".payment-options-container input[type='checkbox']").not(paymentCheckbox).removeAttr("checked").parents(".payment-option-segment").removeClass("active");
        paymentCheckbox.parents(".payment-option-segment").addClass("active");
        paymentCheckbox.prop('checked', true);
    });

    //Shipping location
    $('body').on('change', ".checkout-ship-location-table input[type='checkbox']", function() {
        if($(this).is(':checked')){
            $(".checkout-ship-location-table input[type='checkbox']").not($(this)).removeAttr("checked").parents(".tr-address").removeClass("active");
            $(this).parents(".tr-address").addClass("active");
        } else {
            $(this).prop('checked', true);
        }
    });

    $(".checkout-ship-location-table input[type='checkbox']").each(function(){
        if($(this).is(':checked')){
            $(this).parents(".tr-address").addClass("active");
        }
    });

    $('body').on('click', ".checkout-ship-location-table .item-name, .checkout-ship-location-table .item-address-line", function() {
        $(this).parents(".tr-address").find("input[type='checkbox']").trigger("click");
    });

    //New Address modal
    $(".add-address-button").click(function(evt){
        evt.preventDefault();
        $(".new-address-modal").modal("show");
    });

    //New Address modal
    $('body').on('click', '.delete', function() {
        $(".delete-address-modal").modal("show");
    });

    //Edit modal
    $('body').on('click', '.shipping-address-segment .edit', 'click', function(){
        $(".edit-address-modal").modal("show");
    });

    //Edit mobile number for registered user
    $('.edit-mobile-phone-number-trigger').on('click', function (){
        $(".edit-mobile-phone-number-modal").modal("show");
    });

    //Edit mobile number for registered user
    $('.verify-contact-number-trigger').on('click', function (){
        $(".verify-contact-number-modal").modal("show");
    });

    // user address form validation
    var $userAddressForm = $('form[name="user_address"]');
    if ($userAddressForm.length > 0) {
        var userAddressFormFields = {};

        var $requiredFields = $userAddressForm.find('[required="required"]');
        $requiredFields.each(function() {
            var fieldName = $(this).attr('name');
            userAddressFormFields[fieldName] = {
                identifier: fieldName,
                rules: [{type: 'empty'}]
            };
        });

        $userAddressForm.form({
            fields: userAddressFormFields
        });
    }

    $userAddressForm.on('submit', function(evt) {
        var consigneeName = $('[name="consigneeName"]').val();
        var consigneeContactNumber = $('[name="consigneeContactNumber"]').val();
        $userAddressForm.attr('action', '?consigneeName='+consigneeName+'&consigneeContactNumber='+consigneeContactNumber);
    });

    var heightOfShipLocation = $(".checkout-ship-location-table").outerHeight();
    if(heightOfShipLocation > 425){
        $(".long-text-mask").show();
    }
    else{
        $(".long-text-mask").hide();
    }

    $(".long-text-mask .see-more").click(function(){
        
        $(".checkout-ship-location-container").toggleClass("active");
        $(".long-text-mask").toggleClass("active");
        var $this = $(this);

        var text = $this.text().trim();
        var locale = $this.attr('locale');
        if(text == 'See More' || text == "查看更多"){
            $this.text((locale === 'cn')? '少量显示' : 'Show Less');
        }
        else{
            $this.text((locale === 'cn') ? '查看更多' : 'See More');
        }
    });

    $mobileVerification = $('[data-mobile-verification]');
    if ($mobileVerification.length) {
        $mobileVerification.on('mobile.verification.success', function() {
            var $errorMessage = $('.message-box.red');
            if ($errorMessage.text().indexOf('Mobile') > -1) {
                $errorMessage.hide();
            }
        });
    }


    /* START VOUCHER */
    var $checkoutVoucher = $('[data-checkout-voucher]');
    if ($checkoutVoucher.length) {
        var toMoney = function(amount) {
            return amount.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
        };
        var url = Routing.generate('checkout_validate_voucher');

        $voucherSegment = $('#voucher-segment');
        $voucherSegment.yiLoader();

        var $applyBtn = $voucherSegment.find('button');
        $applyBtn.on('click', function(evt) {
            evt.preventDefault();
            var value = $checkoutVoucher.val();
            if (value) {
                $.ajax({
                    beforeSend: function() {
                        $voucherSegment.trigger('loader.start');
                    },
                    url: url+'?code='+value,
                    success: function(data) {
                        if (data.isSuccessful) {
                            $checkoutVoucher.trigger('voucher.success', data);
                        }
                        else {
                            $checkoutVoucher.trigger('voucher.error', data);
                        }
                    },
                    complete: function() {
                        $voucherSegment.trigger('loader.stop');
                    }
                });
            }
            else {
                $checkoutVoucher.trigger('voucher.empty');
            }
        });

        var $voucherDiscount = $('[data-voucher-discount]');
        var $grandTotal = $('[data-checkout-grand-total]');
        var $checkoutTotal = $('[data-checkout-total]');
        var $voucherError = $('[data-voucher-error]');
        var $voucherSuccess = $('[data-voucher-success]');
        var defaultVoucherError = $voucherError.text();
        $checkoutVoucher.on('voucher.success', function(evt, data) {
            if (parseInt(data.data.less) <= 20) {
                $('[data-cod-container]').click();
            }
            $voucherDiscount.text(data.data.less).closest('tr').show();
            $checkoutTotal.text(data.data.voucherPrice);
            $grandTotal.text(data.data.origPrice).closest('tr').show();
            $voucherError.hide();
            $voucherSuccess.show();
        });

        $checkoutVoucher.on('voucher.error voucher.empty', function(evt, data) {
            $voucherDiscount.closest('tr').hide();
            $checkoutTotal.text($checkoutTotal.data('checkout-total'));
            $grandTotal.text($checkoutTotal.data('checkout-total'));
            $voucherError.text(data && data.message ? data.message: defaultVoucherError);
            $voucherError.show();
            $voucherSuccess.hide();
        });
    }
    /* END VOUCHER */

    var $paymentForm = $('form[name="payment"]');
    var $paymentTypeError = $('<div class="container"><div class="message-box red">Please choose a payment type</div></div>');
    if ($paymentForm.length > 0) {
        $paymentForm.on('submit', function(evt) {
            var formData = $paymentForm.serialize();
            var formData = formData.split('&');
            var data = {};
            formData.forEach(function(value) {
                var datum = value.split('=');
                data[datum.shift()] = datum.shift();
            });

            if (!data.paymentType) {
                evt.preventDefault();
                $('.checkout-container').prepend($paymentTypeError);
                $('html, body').scrollTop(0);
            }
            else {
                $paymentTypeError.remove();
            }
        });
    }

    /* GUEST VALIDATION */
    var $formUserGuest = $('form[name="user_guest"]');
    if ($formUserGuest.length && typeof $formUserGuest.form == 'function') {
        $formUserGuest.form({
            fields: {
                'user_guest[firstName]': {
                    identifier: 'user_guest[firstName]',
                    rules: [{
                        type: 'empty',
                        prompt: 'user_guest[firstName]|Firstname is required'
                    }]  
                },
                'user_guest[lastName]': {
                    identifier: 'user_guest[lastName]',
                    rules: [{
                        type: 'empty',
                        prompt: 'user_guest[lastName]|Lastname is required'
                    }]
                },
                'user_guest[email]': {
                    identifier: 'user_guest[email]',
                    rules: [{
                        type: 'empty',
                        prompt: 'user_guest[email]|Email is required'
                    }]
                },
                'user_guest[contactNumber]': {
                    identifier: 'user_guest[contactNumber]',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'user_guest[contactNumber]|Contact Number is required'
                        }, 
                        {
                            type: 'minLength[10]',
                            prompt: 'user_guest[contactNumber]|Please enter atleast 10 characters'
                        }
                    ]
                }
            },
            onSuccess: function() {
                $formUserGuest.find('.form-error-prompt').hide();
            },
            onFailure: function(errors) {
                $formUserGuest.find('.form-error-prompt').hide();

                $.each(errors, function(index, value) {
                    var error = value.split('|');
                    var $elem = $('[name="'+error.shift()+'"]');
                    $elem
                        .next('.form-error-prompt')
                        .show()
                        .html('<ul><li>'+error+'</li></ul>')
                    ;
                });

                return false;
            }
        });
    }
    
    var $guestOTPCountdown = $('#otp-time-limit');    
    $('[data-checkout-verify-number], [data-guest-checkout-verify-number]').on('otp.stop', function(status, response){

        var $guestCheckoutFormError = $(".guest-checkout-form-error");

        if(parseInt(response.status, 10) === 200){
            var responseData = $.parseJSON(response.response);
            $guestOTPCountdown.countdown(responseData.data.expiration.replace('.000000', ''), function(event) {
                $(this).text(
                    event.strftime('%M:%S')
                );
            });
            $(".verify-guest-contact-number-modal").modal("show");
            $guestCheckoutFormError.hide();            
        }
        else{           
            var responseString = response.response;
            var resposeJson = $.parseJSON(responseString);            
            $guestCheckoutFormError.html(resposeJson.message)
                .show().delay(2500)
                .fadeOut(1000);

            var $modalError = $('.contact-error-prompt');
            $modalError.find('.message-box').html(resposeJson.message);
            $modalError.show().delay(2500).fadeOut(1000);
        }
    });

    var validateOTPUrl = Routing.generate('core_validate_token');
    $('[data-verify-continue]').on('click', function() {
        var $verificationCode = $('[data-verification-code]');
        var code = $verificationCode.val();
        if ((''+code).length) {
            var $contactNumber = $('#user_guest_contactNumber');
            if (!$contactNumber.length) {
                $contactNumber = $('#checkout_verify_contact_number');
            }
            var contactNumber = $contactNumber.val();
            $.ajax({
                url: validateOTPUrl,
                method: 'POST',
                beforeSend: function() {
                    $verificationCode.trigger('floating.error-clear');
                    $('[data-guest-checkout-verify-number]').trigger('loader.start');
                },
                data: {
                    contactNumber: contactNumber,
                    verificationCode: code,
                    type: $('[data-send-otp]').data('send-otp')
                },
                success: function(response) {
                    if (response.isSuccessful) {
                        $formUserGuest.find('[name="user_guest[confirmationCode]"]').val(code+':'+contactNumber);
                        if ($formUserGuest.length) {
                            $formUserGuest.submit();
                        }
                        else {
                            location.reload();
                        }
                    }
                    else {
                        $verificationCode.trigger('floating.error', response.message);
                    }
                },
                error: function(response) {
                    $verificationCode.trigger('floating.error', response.responseJSON.message);
                },
                complete: function() {
                    $('[data-guest-checkout-verify-number]').trigger('loader.stop');
                }
            });

        }
        else {
            $verificationCode.trigger('floating.error', 'Verification code is empty');
        }
    });

    $('#checkout-initiate-otp').on('click', function(evt){
        var $elem = $(this);
        if($formUserGuest.form('is valid') === false){
            $elem.addClass('hasFormError');
            /**
             * Throw form errors
             */
            $formUserGuest.submit();
        }
        else{
            $elem.removeClass('hasFormError');
        }
    });


      

})(jQuery);
