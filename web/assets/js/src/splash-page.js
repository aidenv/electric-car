(function($) {
    $(window).on("ready load resize", function(){
        var windowHeight = $(this).outerHeight();
        var heightOfImageWrapper = $(".register-success-splash-image-wrapper").outerHeight();
        var heightOfSplashMessage = windowHeight - heightOfImageWrapper;
        $(".register-success-splash-message-wrapper").css(
        {
            height: heightOfSplashMessage
        }).animate({
            opacity: 1
        });
    });
})( jQuery );