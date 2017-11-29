(function($) {

    var $currentFlashSale = $(".flash-timer.div-view");
    if($currentFlashSale.length > 0){
        var $timer = new Timer();
        $timer.init($currentFlashSale);
        $timer.start(function(){
        }, function(){
            location.reload();
        });
    }

    $('.flash-upcoming-slider, .current-flash-sale-slider').slick({
      dots: false,
      infinite: false,
      speed: 300,
      slidesToShow: 5,
      variableWidth: false,
      draggable: true,
      centerMode: false,
      prevArrow: "<span class='control previous'><i class='icon icon-angle-left'></i></span>",
      nextArrow: "<span class='control next'><i class='icon icon-angle-right'></i></span>",
    });
})(jQuery);
