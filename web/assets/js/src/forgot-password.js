'use strict';

(function($) {
    
	var USER_TYPE_SELLER = 1;
	var STORE_TYPE_RESELLER = 1;

	function checkField($field){

		var $val = $field.val();
		var $error;

		switch($field.attr("name")){
			case "user_forgot_password[request]":
        		$error = FormValidator.validateField($val.trim(), ['required'], null, null, null, 60);
				break;
		}

		if($error === true){
			$field.next().attr("style", "").addClass("hidden").text("").parent(".field").removeClass("error");
		}
		else{
			$field.next().attr("style", "").removeClass("hidden").text($error).parent(".field").addClass("error");
		}
	}

	$(document).ready(function(){
		
		var $ajaxLoading = false;
		var $forgotPasswordForm = $('form[name="user_forgot_password"]');
		var $userType = $forgotPasswordForm.data("user-type");
		var $storeType = $forgotPasswordForm.data("store-type");
		var $responseErrorBox = $forgotPasswordForm.find(".message-box");
		var $forgotPasswordFormSettings = {
            fields : {
	            'user_forgot_password[request]': {
	                identifier  : 'user_forgot_password[request]',
	                rules: [
	                    {
	                        type   : 'empty',
	                        prompt : 'Email or Contact Number is required'
	                    },
	                    {
	                        type   : 'maxLength[60]',
	                        prompt : 'Please enter at most 60 characters'
	                    }
	                ]
	            }
            },
            onSuccess : function(){

                $responseErrorBox.html("");
                $responseErrorBox.addClass("hidden");
                
                if(!$ajaxLoading){
                    var $grecaptcha = $forgotPasswordForm.find("input[name='user_forgot_password[grecaptcha]']");
                    var $captcha = $forgotPasswordForm.find("input[name='user_forgot_password[captcha]']");
                    var captchaType = $grecaptcha.length ? 'grecaptcha': 'captcha';

                    $ajaxLoading = true;
                    $.ajax({
                        url: Routing.generate("core_forgot_password"),
                        type: 'POST',
                        data: {
                            storeType : $storeType,
                        	request : $forgotPasswordForm.find("input[name='user_forgot_password[request]']").val(),
                        	grecaptcha : $grecaptcha.val(),
                            captcha : $captcha.val(),
                            captchaType : captchaType,
                        	token : $forgotPasswordForm.find("input[name='user_forgot_password[_token]']").val()
                        },
                        beforeSend: function(){
                        	applyLoading($forgotPasswordForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                            	if(response.data.type == "email"){
                            		jQuery(".success.modal").modal("show");
                            		resetRecaptcha();
                            	}
                            	else{
                            		window.location.replace(Routing.generate("user_reset_password_verification_code"));
                            	}
                            }
                        },
                        error: function(response){
                            var $captcha = $('[title="captcha"]');
                            if ($captcha.length) {
                                $captcha.attr('src', response.responseJSON.data.code);
                            }
                            var $responseJson = response.responseJSON;
                            var $errors = $responseJson.data.errors;
                            var $errorList = "<ul>";

                            resetRecaptcha();

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
                        	unloadButton($forgotPasswordForm);
                        }
                    });

                }
                
                return false;
            },
            onFailure : function(error){
            	var $fields = $(".two-way-bind");

            	$forgotPasswordForm.removeClass("error");

            	$.each($fields, function($index, $value){
            		checkField($(this));
            	});

            	return false;
            }
        };


        $forgotPasswordForm.form($forgotPasswordFormSettings);  
		
		$forgotPasswordForm.on("focusout", ".two-way-bind", function(){
			checkField($(this));
		});
	});

})(jQuery);