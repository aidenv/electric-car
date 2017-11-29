(function($) {

    var $ajaxLoading = false;
   
    $(".edit-password-trigger").click(function(){
        $(".edit-password-modal").modal("show");
    });

    $(".edit-email-trigger").click(function(){
        $(".edit-email-modal").modal("show");
    });

    $(".success-update-modal .confirm").click(function(){
        $(".success-update-modal").modal("hide");
    });

    $(document).ready(function(){

        var $changePasswordForm = $("form[name='change-password']");
        var $changeEmailForm = $("form[name='change-email']");

        var $changePasswordFormRules = {
            fields: {
                oldPassword: {
                    identifier  : 'oldPassword',
                    rules: [
                      {
                        type   : 'empty',
                        prompt : 'Current Password field is required.'
                      },
                      {
                        type   : 'minLength[8]',
                        prompt : 'Old Password must be at least 8 characters'
                      },
                      {
                        type   : 'maxLength[100]',
                        prompt : 'Please enter at most 100 characters for your old password'
                      }
                    ]
                },
                newPassword: {
                    identifier  : 'newPassword',
                    rules: [
                      {
                        type   : 'empty',
                        prompt : 'New Password field is required.'
                      },
                      {
                        type   : 'minLength[8]',
                        prompt : 'New Password must be at least 8 characters'
                      },
                      {
                        type   : 'maxLength[100]',
                        prompt : 'Please enter at most 100 characters for your new password'
                      }
                    ]
                },
                newPasswordConfirm: {
                    identifier  : 'newPasswordConfirm',
                    rules: [
                      {
                        type   : 'match[newPassword]',
                        prompt : 'Passwords not match.'
                      }
                    ]
                }
            },
            onSuccess: function(){

                if(!$ajaxLoading){
                    $ajaxLoading = true;

                    var $successUpdateModal = $(".success-update-modal");

                    var $oldPassword = $changePasswordForm.form("get value", "oldPassword");
                    var $newPassword = $changePasswordForm.form("get value", "newPassword");
                    var $newPasswordConfirm = $changePasswordForm.form("get value", "newPasswordConfirm");
                    var $responseErrorBox = $(".change-password-errors");
                    var $content = '<div class="sub-header">Please keep your password in a safe location. YiLinker employees will never ask you for your password.</div>';

                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");

                    $.ajax({
                        url: Routing.generate('core_change_password'),
                        type: 'POST',
                        data: {
                            oldPassword : $oldPassword,
                            newPassword : $newPassword,
                            newPasswordConfirm : $newPasswordConfirm
                        },
                        beforeSend: function(){
                            applyLoading($changePasswordForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                $changePasswordForm.form("clear");
                                $successUpdateModal.find(".update-message").html("Password has been changed." + $content);
                                $successUpdateModal.modal("show");
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

                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($changePasswordForm);
                        }
                    });

                    return false;
                }
            }
        };

        var $changeEmailFormFormRules = {
            fields: {
                newEmail: {
                    identifier  : 'newEmail',
                    rules: [
                      {
                        type   : 'empty',
                        prompt : 'New Email field is required.'
                      },
                      {
                        type   : 'maxLength[60]',
                        prompt : 'Please enter at most 60 characters'
                      }
                    ]
                },
                newEmailConfirm: {
                    identifier  : 'newEmailConfirm',
                    rules: [
                      {
                        type   : 'match[newEmail]',
                        prompt : 'Email not match.'
                      },
                      {
                        type   : 'maxLength[60]',
                        prompt : 'Please enter at most 60 characters'
                      }
                    ]
                }
            },
            onSuccess: function(){

                if(!$ajaxLoading){
                    $ajaxLoading = true;

                    var $successUpdateModal = $(".success-update-modal");

                    var $newEmail = $changeEmailForm.form("get value", "newEmail");
                    var $newEmailConfirm = $changeEmailForm.form("get value", "newEmailConfirm");
                    var $responseErrorBox = $(".change-email-errors");
                    var $content = '<div class="sub-header">Please secure your personal email at all times. YiLinker always assumes that any communication or transaction between your email and YiLinker account was authorized by you.</div>';

                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");

                    $.ajax({
                        url: Routing.generate('core_change_email'),
                        type: 'POST',
                        data: {email:$newEmail},
                        beforeSend: function(){
                            applyLoading($changeEmailForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                $(".unverified-email").addClass("hidden");
                                $(".verified-email").removeClass("hidden");
                                $changeEmailForm.form("clear");
                                $successUpdateModal.find(".update-message").html("Email has been changed." + $content);
                                $successUpdateModal.modal("show");
                                $("input[name='currentEmail']").val($newEmail);
                            }
                        },
                        error: function(response){
                            var $responseJson = response.responseJSON;
                            var $message = $responseJson.message;
                            var $errorList = "<ul><li>" + $message + "</li></ul>";

                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($changeEmailForm);
                        }
                    });

                    return false;
                }
            }
        };

        $('.request-verify-email').click(function(){
            if(!$ajaxLoading){
                $ajaxLoading = true;

                $.ajax({
                    url: Routing.generate('core_resend_email_verification'),
                    success: function(response) {
                        if(response.isSuccessful){
                            $(".success-resend-email-verification").modal("show");
                        }
                        $ajaxLoading = false;       
                    },
                    error: function(response){
                        $ajaxLoading = false;
                    }
                });
            }
        });

        $changePasswordForm.form($changePasswordFormRules);
        $changeEmailForm.form($changeEmailFormFormRules);
    });

})(jQuery);
