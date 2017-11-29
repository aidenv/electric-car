(function ($) {

    var timer = new Timer();
    var element = $(".timer");

    if(element.length > 0){
        timer.init(element);
        timer.start(function(){}, function(){
            location.reload();
        });
    }

    $(window).load(function(){
         $("html,body").trigger("scroll");
    });

    $(document).ready(function(){

        var $storage = window.sessionStorage;
        var $isRedirectionEnabled = $storage.getItem("isRedirectionEnabled");

        $("img.lazy.immediate")
        .lazyload({
         event: "lazyload",
         effect: "fadeIn",
         effectspeed: 2000,
         placeholder: "https://d39ndui1cok09h.cloudfront.net/assets/images/uploads/cms/loader-2016.gif"
        })
        .trigger("lazyload");

        $("img.lazy:not(.immediate)")
        .lazyload({
          effect: "fadeIn",
          placeholder: "https://d39ndui1cok09h.cloudfront.net/assets/images/uploads/cms/loader-2016.gif"
        });

        if(/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            window.location = $("[data-app-name]").data("app-name")
        }

        if (window.location.hash == '#_=_') {
            history.replaceState({}, document.title, "/");
        }

        //Slick slider for homepage
        $('.home-slider-top, .home-slider-bottom').slick({
            autoplay: true,
            dots: true,
            customPaging : function(slider, i) {
                return '<span class="page number"></span>';
            },
            prevArrow: "<span class='control previous'><i class='icon icon-angle-left'></i></span>",
            nextArrow: "<span class='control next'><i class='icon icon-angle-right'></i></span>",
        });

        //Top brands sync slider
        $('.brand-name-slider').slick({
            dots: false,
            infinite: true,
            speed: 300,
            slidesToShow: 5,
            slidesToScroll: 1,
            centerMode: false,
            variableWidth: true,
            arrows: true,
            focusOnSelect: true,
            draggable: false,
            asNavFor: '.brand-item-slider',
            prevArrow: "<span class='control previous'><b>&larr;</b></span>",
            nextArrow: "<span class='control next'><b>&rarr;</b></span>",
        });

        $('.brand-item-slider').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            draggable: false,
            asNavFor: '.brand-name-slider'
        });

        //Product slider with featured item on the left side
        $(".product-card-slider").slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            arrows: true,
            draggable: false,
            prevArrow: "<span class='control previous'><b>&larr;</b></span>",
            nextArrow: "<span class='control next'><b>&rarr;</b></span>"
        });

        //Product slider for full width
        $(".product-card-slider-full").slick({
            slidesToShow: 6,
            slidesToScroll: 1,
            arrows: true,
            draggable: false,
            prevArrow: "<span class='control previous'><b>&larr;</b></span>",
            nextArrow: "<span class='control next'><b>&rarr;</b></span>",
            responsive:[
                {

                    breakpoint: 768,
                    settings:{
                        slidesToShow: 3,
                        adaptiveHeight: true,
                        draggable: true,
                    }
                },
                {

                    breakpoint: 500,
                    settings:{
                        slidesToShow: 2,
                        adaptiveHeight: true,
                        draggable: true,
                    }
                },
            ]
        });

        //New homepage main slider
        $(".main-banner-slider").slick({
            autoplay: true,
            dots: true,
            draggable: false,
            arrows: true,
            adaptiveHeight: true,
            customPaging : function(slider, i) {
                return '<span class="page number"></span>';
            },
            prevArrow: "<span class='control previous'>&larr;</span>",
            nextArrow: "<span class='control next'>&rarr;</span>",
        });

        $('.main-select-tab').slick({
            dots: false,
            infinite: true,
            speed: 300,
            slidesToShow: 5,
            slidesToScroll: 1,
            centerMode: false,
            variableWidth: true,
            arrows: true,
            focusOnSelect: true,
            draggable: false,
            asNavFor: '.main-tab-container',
            prevArrow: "<span class='control previous'><b>&larr;</b></span>",
            nextArrow: "<span class='control next'><b>&rarr;</b></span>",
        });

        $('.main-tab-container').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            draggable: false,
            adaptiveHeight: true,
            asNavFor: '.main-select-tab'
        });

        $(".tab-inner-slides").slick({
            autoplay: false,
            dots: true,
            draggable: false,
            arrows: false,
            adaptiveHeight: true,
            customPaging : function(slider, i) {
                return '<span class="page number"></span>';
            },
            prevArrow: null,
            nextArrow: null,
        });

        //Match height for uneven containers
        $(".category-item-wrapper .category-children-container").matchHeight();

        //Category board content
        var categoryContainer = $(".all-category-container");
        var allCategoryBoard = $(".all-category-container .category-board");

        $(".all-category-container .list-all-categories > li > a").mouseover(function(e){
            e.preventDefault();
            var thisCategory = $(this);
            var thisCategoryHref = $(this).attr("data-href");

            $(".all-category-container .list-all-categories > li > a").not(thisCategory).removeClass("active");
            thisCategory.addClass("active");
            allCategoryBoard.not(thisCategoryHref).removeClass("active");
            categoryContainer.find(thisCategoryHref).addClass("active");
            $(".overlay-category").show();
        });

         $(".overlay-category").mouseover(function(){
            $(".list-all-categories > li > a").removeClass("active");
            allCategoryBoard.removeClass("active");
            $(this).hide();
         });
    });

    $(window).on("load", function(){
        $(".home-slider-top,.product-card-slider,.home-slider-top, .product-card-slider-full").show();
        $(".home-slider-dummy").remove();

        $(".app-banner-container").show().animate({
            bottom: "0px"
        }, 300);

        $(".close-app-ad").on("click", function(){
            $(".app-banner-container").animate({
                bottom: "-60px"
            }, 300, function(){
                $(this).remove();
            });
        });
    });

    $(window).on("load ready resize scroll", function(){
        var homeSectionAds = $('.home-section.ads').offset();

        if(typeof homeSectionAds != "undefined"){
            topRightAdsOffset = homeSectionAds.top;

            if ($(this).scrollTop() > topRightAdsOffset){
                $(".category-nav-trigger").slideDown("fast", function(){
                    $(".shop-by-category").css({
                        "display": "table-cell"
                    });
                });
            }
            else{
                $(".category-nav-trigger").slideUp("fast", function(){
                    $(".shop-by-category").css({
                        "display": "none"
                    });
                });
            }
        }
    });

    if (typeof $.fn.matchHeight == 'function') {
        $('.col-perk-container').matchHeight({
            byRow: true
        });
    }
})(jQuery);
