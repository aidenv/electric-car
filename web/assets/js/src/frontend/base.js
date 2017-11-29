(function ($) {

    var $storage = window.sessionStorage;
    var $isSuggestionClosed = $storage.getItem("isSuggestionClosed");

    // prevents multiple form submits
    window.onbeforeunload = function() {
        $('form').on('submit', function(evt) {
            evt.preventDefault();
        });
    };

    // enables string interpolation
    String.prototype.interpolate = function(data) {
        if (typeof this.replace != 'function') {
            return this;
        }

        return this.replace(/{([^{}]*)}/g, function(a, b) {
            var r = data[b];
            return typeof r === 'string' || typeof r === 'number' ? r : a;
        });
    };

    //Call for tooltip
    $(".tooltip").tipso({
        speed: 200,
        background: '#000000',
        color: '#ffffff',
        width: 100,
        maxWidth: 200
    });

    $(".tooltip-cart").tipso({
        speed: 200,
        background: '#000000',
        color: '#ffffff'
    });

    //Dropdown menu
    $(".navbar-dropdown").click(function(){
        if(!($(this).parents(".navbar-item").hasClass("active"))){
            $(".navbar-item.active").find(".navbar-dropdown-container").transition("scale");
            $(".navbar-item.active").removeClass("active");
        }

        $(this).parents(".navbar-item").find(".navbar-dropdown-container").transition("scale");
        $(this).parents(".navbar-item").toggleClass("active");
    });

    $(document).click(function(e) {
         var target = e.target;
        if (!$(target).is('.navbar-dropdown') && !$(target).parents().is('.navbar-dropdown') && $(".navbar-dropdown-container").is(":visible")) {
            $(".navbar-item.active").find($('.navbar-dropdown-container')).transition("scale").hide();
            $(".navbar-dropdown-container").parents(".navbar-item").removeClass("active");
        }
    });

    //Single selection select box
    $(".single.selection, .ellipsis-dropdown, .filter.dropdown").dropdown();

    //Multiple selection select box with tokens
    $(".multiple.search.selection").dropdown({
        maxSelections: 5,
        allowAdditions: true
    });

    //Checkbox customize design
    $(".ui.checkbox").checkbox();

    //Add extra padding for dropdown itemms
    $('.ui.dropdown .item, .ui.selection.dropdown .menu>.message, .category-dropdown').attr('style', 'padding: 0.5em 1.25em !important');
    $('.ui.dropdown .item, .menu>.message').attr('style', 'padding: 1em 1.25em !important');

    if (typeof hinclude != typeof undefined) {
        // adjusted product images for hincluded templates
        var hincludeShowBufferedContent = hinclude.show_buffered_content;
        hinclude.show_buffered_content = function() {
            hincludeShowBufferedContent();
            adjustImageDisplay();
        };
    }
    var adjustImageDisplay = function() {
        //Assign image height of the product container
        var widthOfImageWrapper = $(".image-display").outerWidth();
        var getTheAdditionalHeight = widthOfImageWrapper*0.04;
        var heightOfImageWrapper = widthOfImageWrapper + getTheAdditionalHeight;

    };

    var productCartButtonFlipDisplay = function() {
        //Flip add to cart button when click
        $(".product-cart-button-flip").flip({
            trigger: "click",
            autoSize: false,
            axis: "x"
        });
    };

    productCartButtonFlipDisplay();

    $(document).ajaxComplete(adjustImageDisplay);
    $('body').on('adjustImageDisplay', adjustImageDisplay);
    $(window).on("ready load resize", adjustImageDisplay);

   //Lazy Load
    $(window).on("ready load scroll", function(){
        $('.lazy-block').each(function(i){
            var bottom_of_object = $(this).position().top + $(this).outerHeight();
            var bottom_of_window = $(window).scrollTop() + $(window).height() + 250;
            bottom_of_window = bottom_of_window + 50;
            if( bottom_of_window > bottom_of_object ){
                $(this).animate({
                    'opacity':'1'
                },300)
            }
        });
    });

    //Custom scrollbar
    $(window).on("load", function(){
        $('.list-suggested-search').mCustomScrollbar();
        $(".product-cart-button-flip .button.gray").show();
    });

    $('.user-profile-trigger').on('click', function(event){
        event.preventDefault();
        $(".user-card-modal").modal("show");
    });

    $('.subscribe-newsletter').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('.button');
        var $emailInput = $form.find("[name='email_newsletter[email]']");

        $.ajax({
            url: Routing.generate('subscribe_email'),
            method: 'POST',
            type: 'JSON',
            data: {
                'email': $emailInput.val(),
            },
            beforeSend: function(){
                $submitButton.addClass('disabled');
            },
            success: function(response) {
                $submitButton.removeClass('disabled');
                var $promptMessageContainer;
                if(response.isSuccessful){
                    $promptMessageContainer = $form.find('.subscribe-success-message');
                    $emailInput.val('');

                    /**
                     * Facebook Pixel CompleteRegistration tracker
                     */
                    if(typeof fbq != "undefined"){
                        fbq('track', 'CompleteRegistration', {
                            content_name: 'successful_email_subscription',
                            status: 'successful'
                        });
                    }
                }
                else{
                    $promptMessageContainer = $form.find('.subscribe-fail-message');
                    $promptMessageContainer.html(response.message);
                }
                $promptMessageContainer.show().delay(3000).fadeOut();
            },
        });
    });

    var isGettingSearchSuggestions = false;
    $(".navbar-search-field").on("keyup", function () {

        if(isGettingSearchSuggestions){
            return false;
        }
        var queryString = $(this).val();
        var $suggestionContainer = $(".suggested-search-container");
        if (queryString.length >= 3) {
            $.ajax({
                url: Routing.generate('api_product_searchkeywords'),
                method: 'GET',
                type: 'JSON',
                data: {
                    'queryString': queryString,
                },
                beforeSend: function(){
                    isGettingSearchSuggestions = true;
                },
                success: function(response) {
                    isGettingSearchSuggestions = false;
                    var $list = $suggestionContainer.find('.list-suggested-search');
                    if(response.data.length > 0){
                        var htmlString = "";
                        $.each(response.data, function(){
                            htmlString += "<li><a href='"+this.webSearch+"'>"+this.keyword+"</a></li>"
                        });
                        $list.html('');
                        $list.append(htmlString);
                        $('.list-suggested-search').mCustomScrollbar("update");
                        $suggestionContainer.show();
                    }
                    else{
                        $list.html('');
                        $suggestionContainer.hide();
                    }
                },
            });
        }
        else {
            $suggestionContainer.hide();
        }
    });

    if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) && $isSuggestionClosed == null) {
        var $button = $(".download-app");
        var $iosLink = $button.data("ios");
        var $androidLink = $button.data("android");
        var $directDownload = $(".direct-download");
        var $apkLink = $directDownload.data("apk");
        var $isShown = false;

        if(/iPhone|iPad|iPod/i.test(navigator.userAgent) && $iosLink != null && $iosLink != ""){
            $button.attr("href", $iosLink);
            $isShown = true;
        }

        if(/Android|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) && $androidLink != null && $androidLink != ""){

            $directDownload.show();
            $directDownload.attr("href", $apkLink);
            $button.attr("href", $androidLink);
            $isShown = true;
        }

        if($isShown){
            $(".get-mobile-app-container").show();
            $(".footer-wrapper").addClass("mobile-app-link-active");
        }
    }

    $(".get-mobile-app-container .icon.close").on("click", function(){
        $(".get-mobile-app-container").hide();
        $(".footer-wrapper").removeClass("mobile-app-link-active");
        $storage.setItem("isSuggestionClosed", true);
        $isSuggestionClosed = true;
    });

    $(".navbar-item.hover").on("mouseover", function(){
        $(".navbar-item.active").find($('.navbar-dropdown-container')).transition("scale").hide();
        $(".navbar-dropdown-container").parents(".navbar-item").removeClass("active");
        $(this).find(".navbar-dropdown-container").removeClass("hidden");
        $(this).find(".link-button").addClass("active");
    });

    $(".navbar-item.hover").on("mouseout", function(){
        $(this).find(".navbar-dropdown-container").addClass("hidden");
        $(this).find(".link-button").removeClass("active");
    });

    var $matchHeight = $('.product-match-height');
    var $sellerMatchHeight = $('.seller-wrapper');
    if (typeof $matchHeight.matchHeight == 'function') {
        $matchHeight.matchHeight({
            byRow: true
        });

        $sellerMatchHeight.matchHeight({
            byRow: true
        });
    }

    $(".category-nav-trigger").on("click", function(){
        $(".all-categories-bubble").transition({
            animation: "scale",
            onHide: function(){
                closeCategoryBoard();
                $(".nav-header.dimmer").removeClass("active");
                $("body").removeClass("dimmed");
            },
            onShow: function(){
                $(".nav-header.dimmer").addClass("active");
                $("body").addClass("dimmed");
            }
        });
    });

    //Category board content

        var categoryContainer = $(".all-categories-bubble");
        var allCategoryBoard = $(".all-categories-bubble .category-board");

    $(".all-categories-bubble .list-all-categories > li > a").mouseover(function(e){
        e.preventDefault();
        var thisCategory = $(this);
        var thisCategoryHref = $(this).attr("data-href");

        $(".all-categories-bubble .list-all-categories > li > a").not(thisCategory).removeClass("active");
        thisCategory.addClass("active");
        allCategoryBoard.not(thisCategoryHref).removeClass("active");
        categoryContainer.find(thisCategoryHref).addClass("active");
        $(".overlay-category").show();
    });

     $(".overlay-category").mouseover(function(){
        closeCategoryBoard();
     });

    var closeCategoryBoard = function(){
        $(".list-all-categories > li > a").removeClass("active");
        allCategoryBoard.removeClass("active");
        $(this).hide();
    };

    if (typeof $.fn.matchHeight == 'function') {
        $('.seller-wrapper').matchHeight({
            byRow: true
        });
    }

    // var $matchHeight = $('.product-match-height');
    // if (typeof $matchHeight.matchHeight == 'function') {
    //     $matchHeight.matchHeight({
    //         byRow: true
    //     });
    // }
    if ($.isFunction($.fn.popup)) {
        $('.popup-input').popup({
            on: "focus",
            inline: true,
            position: "bottom left"
        });

        $('.popup-hover').popup({
            on: "hover",
            inline: true,
            position: "bottom left"
        });
    }

    $(".overseas-modal-trigger").on("click", function(){
        $(".overseas-modal").modal("show");
    });
    $(".open-category-mobile").on("click", function(){
        $(".main-container, .left-wing-mobile, .navbar, body").toggleClass("open");
        $("html").toggleClass("overflow-hidden");
    });

    $(".open-search-trigger, .icon.back").on("click", function(){
        $(".navbar-top .default").fadeToggle("fast");
        $(".navbar-top .search-mobile-field-container").fadeToggle("fast");
    });

    $(".checkout-mobile-nav").on("click", function(){
        $(".navbar-simple-default, .checkout-mobile-menu, .checkout-wrapper, .footer-wrapper").toggleClass("open");
    });

    $(document).ready(function(){
        if ($.isFunction($.fn.stickMe)) {
            $('.default-web').stickMe(); // Yes, that's it!
        }
    });
    
    
    //导航条JS
    $('.j-nav-li').on('mouseenter',function(){
    	var $val=$(this).index();
		$('.noe').find('ul').eq($val).show().siblings().hide();
		$(this).addClass('active').siblings().removeClass('active');
	});
	$('.header-nav').on('mouseleave',function(){
		$('.noe').find('.j-submenu').hide();
		$('.j-nav-li').removeClass('active');
	});

})(jQuery);
