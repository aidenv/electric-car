(function ($) {

    $(document).ready(function(){
        $(".static-slider").slick({
            autoplay: false,
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

        $(".static-slider2").slick({
            autoplay: false,
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

        $(".static-slider3").slick({
            autoplay: true,
            dots: false,
            draggable: false,
            arrows: true,
            adaptiveHeight: false,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: "<span class='control previous'>&larr;</span>",
            nextArrow: "<span class='control next'>&rarr;</span>",
        });
    });

})(jQuery);
