var showLoginForm = false;
var onloadCallback = function() {

    var sitekey = jQuery("#login-form").data("sitekey");

    if (jQuery('#g-recaptcha').length) {
        grecaptcha.render("g-recaptcha", {
            'sitekey' : sitekey,
            'callback' : verifyCallback,
            'expired-callback' : expiredCallback
        });
    }
};

var verifyCallback = function(response){
    jQuery("input[name='user_forgot_password[grecaptcha]']").val(response);
};

var expiredCallback = function() {
    jQuery("input[name='user_forgot_password[grecaptcha]']").val("");
};

function resetRecaptcha(){
    if (jQuery('#g-recaptcha').length) {
        grecaptcha.reset();
    }
}

(function ($){

    var url = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
    var pathArray = window.location.pathname.split( '/' );

    var segment = "/" + pathArray[1];

    if(segment == Routing.generate("user_forgot_password_request")){
        $("#login-form .login-form").removeClass("visible").addClass("hidden");
        $(".forgot-password-form").removeClass("hidden").addClass("visible");
    }

    //Tab functions for login and register
    var tabSrc= $(document).find(".login-tab-selection a.active").attr('data-href');
    var $clearableFieldSelector = $("#login-form input[name='request']," +
                                    "#login-form input[name='password'], " +
                                    "#login-form input[name='email'], " +
                                    "#login-form input[name='rememberMe'], " +
                                    "#register-form input[type!='hidden']");
    
    $(".login-tab-selection a").click(function(){
        var $token = $("input[name='token']").data("val");
        showLoginForm = true;
        $(".login-form").removeClass("hidden");
        $(".forgot-password-form").addClass("hidden").removeClass("visible").attr("style", "");
        if (!$(this).hasClass("active")) {
            var $this = $(this);
            var tabLink = $this.attr('data-href');
            var tabName = $(document).find("#"+tabLink);
        
            $('.login-tab-panel').animate({opacity:0},function(){
                $(".login-tab-selection a").not($this).removeClass("active");
                $this.addClass("active");
                $(".login-tab-panel").not(tabName).hide();
                tabName.show();
                $(this).stop().animate({opacity: "1"},"fast");
            });
        }

        if(tabLink == "login-form"){
            window.history.pushState({},"", Routing.generate("user_merchant_login"));
        }
        else if(tabLink == "register-form"){
            window.history.pushState({},"", Routing.generate("user_merchant_register"));
        }

        $("#login-form, #register-form").find('.error').removeClass('error');
        $('#remember_me').prop('checked', false);
        $(".field").removeClass("error");
        $('.form-ui-checkbox').attr('class', 'form-ui-checkbox');
        $('#register-form').find('.form-error-prompt').addClass('hidden');
        $('#register-form').find('.form').attr('class', 'form');
        $('#login-form').find('.message-box').addClass('hidden');
        $("#username, #password, #register-form input[type!='hidden']").val('');
    });

    //Animation transition for forgot password form display
    $(".forgot-password-trigger").click(function() {
        $(".login-form").transition({
            animation: "fly left",
            onComplete : function() {
              $(".forgot-password-form").transition({
                animation: "fly right",
                interval:   500});
            }
        });
        $('#register-form, #login-form').find('.error').removeClass('error');
        $('#remember_me').prop('checked', false);
        $('.form-ui-checkbox').attr('class', 'form-ui-checkbox');
        $('#register-form').find('.form-error-prompt').addClass('hidden');
        $('#register-form').find('.form').attr('class', 'form');
        $('#login-form').find('.message-box').addClass('hidden');
        $clearableFieldSelector.val('');
        $('.form-error-prompt, #generalErrorMessage').hide();
    });

    //Animation transition for forgot password form hide
    $(".forgot-password-hide-trigger").click(function(){
        // showLoginForm = true;
        // $(".login-form").removeClass("hidden");
        $(".forgot-password-form").transition({
            animation: "fade",
            onComplete : function() {
              $(".login-form").transition({
                animation: "fade",
                interval:   500});
            }
        });
    });

    $(document).ready(function () {

        $(document).on('click', '#register-tab, #sign-in-tab', function () {
            $('.form-error-prompt, #generalErrorMessage').hide();
            $clearableFieldSelector.val('');
            $('#register-form').find('.form').attr('class', '');

            var referralCode = getCookie('referralCode');

            if (referralCode !== '') {
                $("#register-form").find("input[name='referralCode']").val(referralCode);
            }
        });

        $('.form-error-prompt, #generalErrorMessage').hide();

        $('#merchant-sign-in').on('click', function () {
            var $this = $(this);
            var email = $('#email').val().trim();
            var password = $('#password').val().trim();
            var $emailDiv = $('#email-div');
            var $passwordDiv = $('#password-div');
            var errorCount = 0;
            var isRememberMe = $('#rememberMe').is(':checked') ? true : false;

            // if (email === '') {
            //     $emailDiv.find('.form-error-prompt').show();
            //     $emailDiv.attr('class', 'form error');
            //     errorCount++;
            // }
            // else {
            //     $emailDiv.attr('class', 'form');
            //     $emailDiv.find('.form-error-prompt').hide();
            // }

            // if (password === '') {
            //     $passwordDiv.find('.form-error-prompt').show();
            //     $passwordDiv.attr('class', 'form error');
            //     errorCount++;
            // }
            // else {
            //     $passwordDiv.attr('class', 'form');
            //     $passwordDiv.find('.form-error-prompt').hide();
            // }

            if (errorCount > 0) {
                $('#generalErrorMessage').show();
                return false;
            }

            $.ajax({
                url : $('#authenticatePath').val(),
                type: 'json',
                method : 'POST',
                data : {
                    _username: email,
                    _password: password,
                    _remember_me: isRememberMe,
                    _csrf_token: $('#csrfToken').val()
                },
                beforeSend: function () {
                    $this.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function (response) {

                    if (response.success == true) {
                        $('#generalErrorMessage').hide();
                        getUserImage();
                    }
                    else {
                        $this.html("Sign In").removeClass('disabled');
                        var $errorContainer = $('#generalErrorMessage');
                        var message = "Invalid email/contact number or password.";                        
                        if(response.message == "Invalid CSRF token."){
                            message = "Please refresh the page and try again. Your page session has expired.";
                        }
                        $errorContainer.html(message);
                        $errorContainer.show();
                    }
                }
            });
        });

        $('#email, #password').on('keypress', function (e) {
            if (e.which == '13') {
                $('#merchant-sign-in').trigger('click');
            }
        });

        supplyReferralCode ($('#domain-container').attr('data-value'));

    });

    function getUserImage ()
    {
        var delay = 1500;
        $.ajax({
            url : $('#getImagePath').val().trim(),
            type: 'json',
            method : 'POST',
            success: function (response) {

                var $verifiedMerchantPhoto = $("#verified-merchant-photo");
                var $verifiedMerchantCover = $("#verified-merchant-cover");
                if (response.avatar !== $verifiedMerchantPhoto.attr('src')) {
                    $verifiedMerchantPhoto.attr('src', response.avatar);
                    $verifiedMerchantPhoto.animate({top: "0"}, "slow");
                }

                if (response.banner !== $verifiedMerchantCover.attr('src')) {
                    $verifiedMerchantCover.attr('src', response.banner);
                    $verifiedMerchantCover.delay(500).animate({top: "0"}, "slow");
                }

                setTimeout(function() {
                    window.location.replace(Routing.generate("home_page"));
                }, delay);
            }
        });
    }

    //Set the login wrapper in the middle vertical align
    $(window).on("ready load resize", function(){
        var windowWidth = $(this).width();
        var windowHeight = $(this).outerHeight();
        var heightOfLoginWrapper = $(".login-wrapper-reset-password").outerHeight();
        var widthOfLoginWrapper = $(".login-wrapper-reset-password").width();
        
        var negativeMarginLeftOfLoginWrapper = 0-(widthOfLoginWrapper/2);
        var negativeMarginTopOfLoginWrapper = 0-(heightOfLoginWrapper/2);

        if(windowHeight<555){
            $(".login-wrapper-reset-password").removeClass("login-buyer").removeAttr("style").css({marginTop: "20px"});
        }else{
            $(".login-wrapper-reset-password").addClass("login-reset-password");
        }

        $(".login-buyer").animate({
            marginTop: negativeMarginTopOfLoginWrapper,
            opacity: 1
            //marginLeft: negativeMarginLeftOfLoginWrapper
        });
    });

    $(".language-dropdown").dropdown({
        on: "hover"
    });
})(jQuery);
