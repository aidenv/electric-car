(function ($) {

    $('.image-slider').slick({
        autoplay: true,
        dots: true,
        adaptiveHeight: true,
        customPaging : function(slider, i) {
            return '<span class="page number"></span>';
        },
        prevArrow: "<span class='control previous'><i class='icon-arrow-left'></i></span>",
        nextArrow: "<span class='control next'><i class='icon-arrow-right'></i></span>",
    });


    $('.brand-slider').slick({
        autoplay: true,
        slidesToShow: 9,
        prevArrow: "<span class='control previous'><i class='icon-angle-left'></i></span>",
        nextArrow: "<span class='control next'><i class='icon-angle-right'></i></span>"
    });

    //For category header list
    $(".expand-category-trigger").click(function(){
        var listCategoryHeight = $(".list-search-result-type").outerHeight() + 7;

        $(".search-category-header").animate({
            height: listCategoryHeight
         });

        $(this).transition({
            animation: "scale",
            onComplete : function() {
                $(".compress-category-trigger").transition({
                    animation: "scale",
                    interval:   500
                });
            }
        });
    });

    $(".compress-category-trigger").click(function(){
        $(".search-category-header").animate({
            height: "33px"
         });

        $(this).transition({
            animation: "scale",
            onComplete : function() {
                $(".expand-category-trigger").transition({
                    animation: "scale",
                    interval:   500
                });
            }
        });
    });

    $(document).on("load scroll", function(){
        $(".list-search-category > li").each(function(){
            var distanceFromTopPage = $(this).offset().top + 28;
            var windowTop = $(window).scrollTop();
            var SubCategoryContainerTop = distanceFromTopPage-windowTop;

            $(this).find(".category-bottom-header").css({
                top: SubCategoryContainerTop
            });
        });
    });

    $(document).on("ready load resize", function(){
        var listCategoryHeight = $(".list-search-result-type").outerHeight();

        if(listCategoryHeight<29){
            $(".search-header-more").css({
                display: "none"
            });
        }
        else{
            $(".search-header-more").css({
                display: "table-cell"
            });
        }
    });
}(jQuery));
