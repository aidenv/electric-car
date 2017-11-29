var onloadCallback = function(){

    var sitekey = jQuery("div.login-tab-panel").data("sitekey");

    if (jQuery('#g-recaptcha').length) {
        grecaptcha.render("g-recaptcha", {
            'sitekey' : sitekey,
            'callback' : verifyCallback,
            'expired-callback' : expiredCallback
        });
    }
}

var verifyCallback = function(response){
    jQuery("input[name='user_forgot_password[grecaptcha]']").val(response);
};

var expiredCallback = function() {
    jQuery("input[name='user_forgot_password[grecaptcha]']").val('');
};

function resetRecaptcha(){
    if (jQuery('#g-recaptcha').length) {
        grecaptcha.reset();
    }
}

(function($) {

    var url = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
    var pathArray = window.location.pathname.split( '/' );

    var segment = "/" + pathArray[1];

    if(segment == Routing.generate("user_buyer_forgot_password_request")){
        reposLogo();
        openForgotPasswordRequest();
    }

    $(window).on("load resize", function(){

        reposLogo();

        $(".back-slider .item, .front-slider .item, .login-taglines .item").css({
            width: $(window).width()
        });

        //Animation transition for forgot password form display
        $(".forgot-password-trigger").click(function(){
            $(".login-form").transition({
                animation: "fade",
                onComplete : function() {
                  $(".forgot-password-form").transition({
                    animation: "fade",
                    interval:   500});
                }
            });

            $("#login-form, #register-form").find('.error').removeClass('error');
            $('#username, #password, #register-form :input').not("input[name='areaCode']").val('');
            $('#remember_me').prop('checked', false);
            $('.form-ui-checkbox').attr('class', 'form-ui-checkbox');
            $('#register-form').find('.form-error-prompt').addClass('hidden');
            $('#register-form').find('.form').attr('class', 'form');
            $('#login-form').find('.message-box').addClass('hidden');
            $("#login-form input[name='request'], #login-form input[name='password'], #login-form input[name='email'], #login-form input[name='rememberMe'], #register-form input").not("input[name='areaCode']").val('');
            $('.form-error-prompt, #generalErrorMessage').hide();
        });
    });
    //Set the login wrapper in the middle vertical align
    $(window).on("load", function(){
        $(".login-tagline-dummy, .front-slider-dummy, .back-slider-dummy").remove();
        $(".login-taglines, .front-slider, .back-slider").show();

        //Slick call for sliders
        $('.login-taglines, .back-slider, .front-slider').slick({
                autoplay: true,
                arrows: false,
                focusOnSelect: false,
                draggable: false,
                pauseOnHover: false,
                speed: 1000,
                variableWidth: true,
                centerMode: true
        });
    });

    //Tab functions for login and register
    var tabSrc= $(document).find(".login-tab-selection a.active").attr('data-href');

    $(".login-tab-selection a").click(function(){
        var $token = $("input[name='token']").data("val");

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

        supplyReferralCode ($('#domain-container').attr('data-value'));

        $("#login-form, #register-form").find('.error').removeClass('error');
        $("input[name='token']").val($token);
        $(".field,.form").removeClass("error");
        $('#username, #password, #register-form :input').not("input[name='areaCode']").val('');
        $('#remember_me').prop('checked', false);
        $('.form-ui-checkbox').attr('class', 'form-ui-checkbox');
        $('#register-form').find('.form-error-prompt').addClass('hidden');
        $('#register-form').find('.form').attr('class', 'form');
        $('#login-form').find('.message-box').addClass('hidden');
        $("#login-form input[name='password'], #login-form input[name='email'], #login-form input[name='rememberMe'], #register-form input").not("input[name='areaCode']").val('');
        $('.form-error-prompt, #generalErrorMessage').hide();

        var referralCode = getCookie('referralCode');

        if (referralCode !== '') {
            $("form[name='register']").find("input[name='referralCode']").val(referralCode);
        }

        if(tabLink == "login-form"){
            window.history.pushState({},"", Routing.generate("user_buyer_login"));
        }
        else if(tabLink == "register-form"){
            $("#register-form").addClass("login-tab-panel");
            window.history.pushState({},"", Routing.generate("user_buyer_register"));
        }
    });

    //Animation transition for forgot password form hide
    $(".forgot-password-hide-trigger").click(function(){
        openForgotPasswordRequest();
    });

    $(document).ready(function() {
        var url = window.location.href;
        if (url.indexOf('register') !=-1) {
            $('.active').removeClass();
            $('#register-tab').trigger('click').addClass('active');
        }

        $("form[name='login']").submit(function(e){
            $("button[type='submit']").html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").attr("disabled", true);
        });

        $('#sendMergeNotification').on('click', function (e) {
            e.preventDefault();
            var $this = $(this);
            var redirectUrl = $this.attr('data-url');
            var email = $this.attr('data-email');

            $.ajax({
                url: Routing.generate('user_merge_social_media_send_notification'),
                method: 'POST',
                dataType: 'json',
                data: {
                    'url': redirectUrl,
                    'email': email
                },
                beforeSend: function () {
                    $this.attr('disabled', true);
                    $this.attr('data-url', '');
                    $this.attr('data-email', '');
                },
                success: function (response) {

                    if (response) {
                        alert('Kindly Check your inbox for notification.');
                        window.location.replace('/login');
                    }
                }
            })
        });
    });

    function openForgotPasswordRequest(){
        $(".forgot-password-form").transition({
            animation: "fade",
            onComplete : function() {
              $(".login-form").transition({
                animation: "fade",
                interval:   500});
            }
        });
    }

    function reposLogo(){
        var logo = $(".logo");
        var logoPosition = logo.position();
        $(".logo-link").css({
            left: logoPosition.left,
            marginTop: 50,
            position: "absolute",
            display: "inline-block",
            width: "57px",
            height: "100px"
        });
    }

    $(".language-dropdown").dropdown({
        on: "hover"
    });
})( jQuery );
