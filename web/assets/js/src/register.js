'use strict';
(function($) {  
    var $registerTimer = null;
    var $cooldownTimer = null;

    var $registerInstance = new Timer();
    var $cooldownInstance = new Timer();

    function checkField($field){

        var $val = $field.val();
        var $locale = $field.attr('locale');
        var $error;

        switch($field.attr("name")){
            case "email":
                $error = FormValidator.validateField($val.trim(), ['required', 'email'], null, null, null, 60, $locale);
                break;
            case "password":
                $error = FormValidator.validateField($val, ['required', 'oneVarcharOneNumber'], null, null, 8, 25, $locale);
                break;
            case "confirmPassword":
                var $password = $("input[name='password']").val();
                $error = FormValidator.validateField($val, ['required', 'matches'], "Password", $password, null, null, $locale);
                break;
            case "contactNumber":
                $error = FormValidator.validateField($val.trim(), ['required', 'integer'], null, null, 10, 20, $locale);
                break;
            case "verificationCode":
                $error = FormValidator.validateField($val.trim(), ['required', 'integer'], null, null, null, null, $locale);
                break;
        }

        if($error === true){
            $field.next().attr("style", "").addClass("hidden").text("").parent(".field").removeClass("error");
            return true;
        }
        else{
            $field.next().attr("style", "").removeClass("hidden").text($error).parent(".field").addClass("error");
            return false;
        }
    }

    $(document).ready(function() {      
        var $ajaxLoading = false;
        var $registerForm = $("form[name='register']");
        var $responseErrorBox = $registerForm.find(".message-box:not(.token-sent)");
        var $actionUrl = $registerForm.data("action");
        var $successUrl = $registerForm.data("callback");
        var $storeType = $registerForm.data("store-type");
        var $language = $registerForm.find('[name="languageId"]'),
            $areaCodeSelect = $registerForm.find(".number-prefix-dropdown");

        // init areaCode drowdown
        $areaCodeSelect.dropdown();
        $language.on('change', function() {
            var languageCode = $(this).find('option:selected').data('code');
            window.location = '?_locale='+languageCode;
        });

        var $registerFormSettings = {
            fields : {
                password: {
                    identifier  : 'password',
                    rules: [
                        {
                            type   : 'regExp[/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9!*_-]+)$/]',
                            prompt : 'This field should contain alphabetical characters with atleast one number.'
                        },
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[25]',
                            prompt : 'Please enter at most 25 characters'
                        },
                        {
                            type   : 'minLength[8]',
                            prompt : 'Please enter atleast 8 characters'
                        }
                    ]
                },
                confirmPassword: {
                    identifier  : 'confirmPassword',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[25]',
                            prompt : 'Please enter at most 25 characters'
                        },
                        {
                            type   : 'minLength[8]',
                            prompt : 'Please enter atleast 8 characters'
                        }
                    ]
                },
                contactNumber: {
                    identifier  : 'contactNumber',
                    rules: [
                        {
                            type   : 'integer',
                            prompt : 'Please enter valid contact number'
                        },
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[20]',
                            prompt : 'Please enter at most 20 characters'
                        },
                        {
                            type   : 'minLength[10]',
                            prompt : 'Please enter atleast 10 characters'
                        }
                    ]
                },             
                verificationCode: {
                    identifier  : 'verificationCode',
                    rules: [
                        {
                            type   : 'integer',
                            prompt : 'Invalid verification code'
                        },
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[6]',
                            prompt : 'Please enter at most 6 characters'
                        },
                        {
                            type   : 'minLength[6]',
                            prompt : 'Please enter atleast 6 characters'
                        }
                    ]
                }
            },
            onSuccess : function(){

                $responseErrorBox.html("");
                $responseErrorBox.addClass("hidden");

                if(!$ajaxLoading){
                    $ajaxLoading = true;
                    $.ajax({
                        url: Routing.generate($actionUrl),
                        type: 'POST',
                        data: {
                            contactNumber : $registerForm.find("input[name='contactNumber']").val(),
                            verificationCode : $registerForm.find("input[name='verificationCode']").val(),
                            password : $registerForm.find("input[name='password']").val(),
                            confirmPassword : $registerForm.find("input[name='confirmPassword']").val(),
                            referralCode : $registerForm.find("input[name='referralCode']").val(),
                            token : $registerForm.find("input[name='token']").data("val"),  
                            areaCode : $registerForm.find("input[name='areaCode']").val(),
                            storeType : $storeType,
                            languageId : $language.length ? $language.val(): 0
                        },
                        beforeSend: function(){                         
                            applyLoading($registerForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                deleteCookie('referralCode');
                                window.location.replace(Routing.generate($successUrl));
                            }
                        },
                        error: function(response){
                            var $responseJson = response.responseJSON;
                            var $errors = $responseJson.data.errors;
                            var $errorList = "<ul>";

                            $errors.forEach(function(value){
                                $errorList += "<li>" + value + "</li>"
                            });

                            $errorList += "</ul>";

                            if($errors.length > 0){
                                $responseErrorBox.html($errorList);
                                $responseErrorBox.removeClass("hidden");
                            }
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($registerForm);
                        }
                    });

                }

                return false;
            },
            onFailure : function(error){
                var $fields = $(".two-way-bind");

                $registerForm.removeClass("error");

                $.each($fields, function($index, $value){
                    checkField($(this));
                });

                return false;
            }
        };


        $registerForm.form($registerFormSettings);

        $registerForm.on("focusout", ".two-way-bind", function(){
            checkField($(this));
        });

        //用手机获取验证码的
        $(".alert-verification-modal-trigger").click(function(){
            var $this = $(this);
            var $field = $registerForm.find("input[name='contactNumber']");
            var $contactNumber = $field.val();
            var $areaCode = $registerForm.find("input[name='areaCode']").val();

            var $isValid = checkField($field);
            if(!$ajaxLoading && $contactNumber != "" && $contactNumber != null && $isValid){
                $ajaxLoading = true;
                $.ajax({
                    url: Routing.generate("core_send_token"),
                    type: 'POST',
                    data: {
                        contactNumber : $contactNumber,
                        storeType : $storeType,
                        areaCode: $areaCode,
                        type : "register"
                    },
                    beforeSend: function(){
                        $this.attr('disabled', true);
                        $this.find(".text").hide();
                        $this.append("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").attr("disabled", true);
                    },
                    error: function(response){
                        var $responseJson = response.responseJSON;
                        var $errors = $responseJson.data.errors;
                        var $errorList = "<ul>";

                        $errors.forEach(function(value){
                            $errorList += "<li>" + value + "</li>"
                        });

                        $errorList += "</ul>";

                        if($errors.length > 0){
                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        }
                    },
                    success: function(response){
                        var $successMessage = $(".token-sent");
                        var $registerTimerContainer = $(".register-timer");

                        if($registerTimer != null){
                            $registerTimer.stop();
                        }

                        $responseErrorBox.html("");
                        $responseErrorBox.addClass("hidden");

                        $successMessage.fadeIn().text("Confirmation code was sent to " + $contactNumber);
                        
                        $successMessage.delay(3000).fadeOut(1000);

                        $registerTimerContainer.attr("data-expiration", moment().add(60, "minutes").unix());

                        $registerTimer = $registerInstance.init($registerTimerContainer);
                        $registerTimer.start(
                            function(){},
                            function(){}
                        );
                    },
                    complete: function(){
                        $ajaxLoading = false;
                        $this.find(".ui.loader").remove();
                        $this.attr("disabled", false);
                        $this.find(".text").show();
                    }
                });

            }
        });
    });

    //Single selection select box
    $(".single.selection, .ellipsis-dropdown, .filter.dropdown").dropdown();

})(jQuery);
