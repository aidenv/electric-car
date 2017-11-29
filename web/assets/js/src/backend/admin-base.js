(function ($) {

    $(document).ready(function(){

        $(".navbar .dropdown").click(function(){

            $(this).toggleClass("toggled");

            if($(this).hasClass("toggled")){
                $(this).animate({
                    left: "-152px"
                });

                $('.sidebar').animate({
                    left: "-132px"
                });
                $('.box-outer').animate({
                    paddingLeft: "60px"
                });
                $('.board').animate({
                    left: "189px"
                });

            }
            else{
                $(this).animate({
                    left: "0px"
                });

                $('.sidebar').animate({
                    left: "0px"
                });
                $('.box-outer').animate({
                    paddingLeft: "189px"
                });
                $('.board').animate({
                    left: "189px"
                });
            }
        });
    });

    //Single selection select box
    $(".single.selection").dropdown();

    $('.stabilizer').each(function(){
      var highestBox = 0;
      $('.box-div', this).each(function(){
          if($(this).height() > highestBox)
             highestBox = $(this).height();
      });
      $('.box-div',this).height(highestBox);
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

})(jQuery);
