(function($) {

    $(document).ready(function () {

        var $youtubeUrl = $('#youtube-url');

        if ($youtubeUrl.length > 0) {
            var youtubeId = getYoutubeIdByUrl($youtubeUrl.val().trim());
            var youtubeVideoUrl = 'https://www.youtube.com/embed/' + youtubeId;
            $('#youtube-frame').attr('src', youtubeVideoUrl);
        }

    });

    var heightOfFullDescription = $(".full-description span").outerHeight();

    if(heightOfFullDescription > 100){
        $(".long-text-mask").show();
    }
    else{
        $(".long-text-mask").hide();
    }

    $(".full-description-container .see-more").click(function(){
        $(".full-description").toggleClass("active");

        $(".long-text-mask").toggleClass("active");

        var text = $(this).text().trim();
        $(this).text(text == "See More" ? "Show Less" : "See More");
    });

    $('.product-photo-slider').slick({
        slidesToShow: 5,
        autoplay: true,
        prevArrow: "<span class='control previous'><i class='icon icon-arrow-left'></i></span>",
        nextArrow: "<span class='control next'><i class='icon icon-arrow-right'></i></span>",
    });

    $(window).on("load resize", function(){
        var imagePhotoslider= $(".item-photo");
        var getTheAdditionalHeight = imagePhotosliderWidth*0.04;
        var imagePhotosliderWidth = imagePhotoslider.width();
        var imagePhotosliderHeight = getTheAdditionalHeight + imagePhotosliderWidth;
        $(".item-photo").each(function(){
            $(this).find(".image-product-photo-holder").css({
                height: imagePhotosliderWidth
            });
        });
    });

    $(".show-prev-remarks").on("click", function() {
        $(".prev-remarks").slideToggle({direction: "up" }, 400);
        $(this).toggleClass("show-txt-remarks");
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

})(jQuery);