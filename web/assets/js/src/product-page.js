(function ($) {
    $(document).on("ready", function() {
        $(".sidebar").css({
            left: "-60px"
        });

        $(".wrapper-outer").css({
            paddingLeft: 0
        });

        $(".navbar .dropdown-menu").addClass("toggled");

        var $youtubeUrl = $('#youtube-url');

        if ($youtubeUrl.length > 0) {
            var youtubeId = getYoutubeIdByUrl($youtubeUrl.val().trim());
            var youtubeVideoUrl = 'https://www.youtube.com/embed/' + youtubeId;
            $('#youtube-frame').attr('src', youtubeVideoUrl);
        }

        var referralCode = getParameterByName('referralCode');

        if (referralCode != '') {
            deleteCookie('referralCode');
            setCookie('referralCode', referralCode, 1);
            var merchantDomain = $('#domain-container').attr('data-value');
            $('#domain-container').append('<img id="domain" src="' + merchantDomain + '/set-cookie/' + referralCode + '">');
        }

    });

    //Get the height of the top layer containers
    $(window).on("ready load resize", function(){
        var windowWidth = $(this).width();
        var windowHeight = $(this).outerHeight();
        var navbarHeight = $(".navbar").outerHeight();
        var productBottomLeftLayer = $(".product-bottom-left-layer").outerHeight();
        var heightOfTopRightLayer = windowHeight - navbarHeight;
        var heightOfRowTopLayer = $(".row-top-layer").outerHeight();
        
       

        $(".sticky-side").stick_in_parent({
            parent: "#productWrapper",
            offset_top: 50
        });

        $(".footer-wrapper").addClass("active-cart-button")
    });

    //Tab functions for seller and short description
    var tabSrc= $(document).find(".product-description-tab a.active").attr('data-href');
    $("#"+tabSrc).show();

    $(".product-description-tab a").click(function(){
        if (!$(this).hasClass("active")) {
            var $this = $(this);
            var tabLink = $this.attr('data-href');
            var tabName = $(document).find("#"+tabLink);

            $('.product-seller-and-short-description-container .tab-item-container').animate({opacity:0},function(){
                $(".product-description-tab a").not($this).removeClass("active");
                $this.addClass("active");
                $(".product-seller-and-short-description-container .tab-item-container").not(tabName).hide();
                tabName.show();
                $(this).stop().animate({opacity: "1"},"slow");
            });
        }
    });

    var tabSrcBottom= $(document).find(".product-bottom-tab a.active").attr('data-href');
    $("#"+tabSrcBottom).show();

    $(".product-bottom-tab a").click(function(){
        if (!$(this).hasClass("active")) {
            var $this = $(this);
            var tabLink = $this.attr('data-href');
            var tabName = $(document).find("#"+tabLink);

            $('.product-bottom-tab-container .tab-item-container').animate({opacity:0},function(){
                $(".product-bottom-tab a").not($this).removeClass("active");
                $this.addClass("active");
                $(".product-bottom-tab-container .tab-item-container").not(tabName).hide();
                tabName.show();
                $(this).stop().animate({opacity: "1"},"slow");
            });
        }
    });

    //Slick slider call for image slider
    $('.product-image-slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        fade: true,
        asNavFor: '.thumbnail-nav',
        draggable: false,
        dots: true,
        prevArrow: "<span class='control previous'><i class='icon icon-angle-left'></i></span>",
        nextArrow: "<span class='control next'><i class='icon icon-angle-right'></i></span>",
        customPaging : function(slider, i) {
            return '<span class="page number"></span>';
        },
        responsive:[
            {
                breakpoint: 768,
                settings:{
                    adaptiveHeight: true,
                    draggable: true,
                }
            }
        ]
    });

    $('.thumbnail-nav').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        asNavFor: '.product-image-slider',
        dots: false,
        centerMode: false,
        focusOnSelect: true,
        vertical: true,
        draggable: false,
        prevArrow: "<div class='thumbnail-arrow arrow-up'><i class='icon icon-arrow-up'></i></div>",
        nextArrow: "<div class='thumbnail-arrow arrow-down'><i class='icon icon-arrow-down'></i></div>"
    });

    //Scroll to complete decription
     $("#scroll-to-description-trigger").on("click", function(){
        $('body, html').animate({
            scrollTop: $("#description").offset().top-50
        }, 1000);
    });

    var $quantityControl = $('[data-quantity-control]');

    if ($quantityControl.length) {
        $quantityControl.on('change', function(evt) {
            var $elem = $(this);
            var target = $elem.data('quantity-control');
            $dataAddToCart = $(target);
            var addtocart = $dataAddToCart.data('addtocart');
            addtocart['quantity'] = $elem.val();
            $dataAddToCart.data('addtocart', addtocart);

            var url = $(this).data('href');

            $.ajax({
                url: url,
                data: addtocart,
                method: 'POST',
                success: function (response) {
                    //$(".product-current-price").html("P "+ response.discountedPrice);
                },
                error: function(response){
                }
            });
        });
    }

    var $unitControl = $('[data-unit-control]');
    $unitControl.on('setUnit', function(evt, unitId) {
        var $elem = $(this);
        var target = $elem.data('unit-control');
        $dataAddToCart = $(target);
        var addtocart = $dataAddToCart.data('addtocart');
        addtocart['unitId'] = unitId;
        $dataAddToCart.data('addtocart', addtocart);

        var $productUnitPartials = $('[data-product-unit-partial="'+unitId+'"]');
        var $productUnitPartialContainers = $('[data-product-unit-partial]').parents(".responsive-show.sticky-cart-button");
            console.log($productUnitPartialContainers);

        $productUnitPartialContainers.removeClass("display-mobile").addClass("hide-mobile");
        $productUnitPartialContainers.find("button-cart").removeClass("display-mobile").addClass("hide-mobile");
        $productUnitPartials.each(function() {
            $productUnitPartial = $(this);
            $productUnitPartial.show();
            $productUnitPartial.siblings('[data-product-unit-partial]:not([data-product-unit-partial="'+unitId+'"])').hide();
            $productUnitPartial.parents(".responsive-show.sticky-cart-button").removeClass("hide-mobile").addClass("display-mobile");
            $productUnitPartial.find("button-cart").removeClass("hide-mobile").addClass("display-mobile");
        });

        $(".out-of-stock").removeClass("display-mobile").addClass("hide-mobile");
    });

    var $imageSlider = $('[data-image-slider]');
    $imageSlider.on('setImageId', function(evt, imageId) {
        var index = $('[data-product-image="'+imageId+'"]').index();
        $('.product-image-slider').slick('slickGoTo', index);
        $('.thumbnail-nav').slick('slickGoTo', index);
    });

    var $attributeChooser = $('#product-attr-chooser');
    $attributeChooser.on('setUnit', function(evt, unitId) {
        var data = $attributeChooser.data('attribute-chooser');
        var productUnit = data[unitId];
	
        var imageId = productUnit.imageIds[0];
        if (imageId > -1) {
	    $imageSlider.trigger('setImageId', imageId);
        }
    });

    $('.product-image-slider').on('beforeChange', function(evt, slick, currentSlide, slide) {
        var $elem = $(this),
            $productImage = $elem.find('[data-product-image]:eq('+slide+')'),
            imageId = $productImage.data('product-image');
        
        $attributeChooser.trigger('imageSelected', imageId);
        $(".item.slick-slide").removeClass("slick-current");
        $(".thumbnail-nav").find(".item.slick-slide[data-slick-index='"+slide+"']").addClass("slick-current"); 
    });

    $(".quantityDropdown").dropdown({
        message: {
            noResults: ''
        }
    });

    $(".video-modal-trigger").on("click", function(){
        $(".video-modal").modal("show");
    });

    /**
     * Get Youtube Id by url
     *
     * @param string url
     * @return string/int
     */
    function getYoutubeIdByUrl (url)
    {
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var match = url.match(regExp);

        if (match && match[2].length == 11) {
            return match[2];
        } else {
            return false;
        }

    }

    $('.popup-country-trigger').popup({
        popup : $('.popup-country'),
        on    : 'hover',
        position: 'bottom right'
    });
})(jQuery);
