(function($) {

    $(document).ready(function(){

        $(".sidebar").css({
            left: "-60px"
        });

        $(".wrapper-outer").css({
            paddingLeft: 0
        });

        $(".navbar .dropdown-menu").addClass("toggled");

        $('.image-overlay').on('click', function(){
            $('.profile-file-input:not(.mobile-change-profile-photo-input)').click();
        });
        
        $(".navbar .dropdown-menu").click(function(){
            if(!$(this).hasClass("toggled")){
                $(".dashboard-side-menu-container").animate({
                    left: "60px"
                });
                
                $(".dashboard-body-container").animate({
                    marginLeft: "24.33em"
                });
            }
            else{
                $('.dashboard-side-menu-container').animate({
                    left: "0px"
                });

                $(".dashboard-body-container").animate({
                    marginLeft: "19.33em"
                });
            }
        });

        $(window).on("load", function(){
            $(".notifications-container, .contacts-container").mCustomScrollbar();
        });

        $(".ui.dropdown").dropdown();
    });

    $(".toggle-side-menu").on("click", function(){
        if(!($(".dashboard-side-menu-container").hasClass("active"))){
            $(".menu-name").animate({
                "opacity": "0"
            }, function(){
                $(this).hide();
            });

            $(".dashboard-side-menu-container").animate({
                "width": "5em"
            }).addClass("active");

            $(".list-side-menu>li").animate({
                "width": "5em"
            });

            $(".dashboard-body-container").animate({
                "margin-left": "5em"
            }, function(){
                $('.shipping-address-container').masonry({
                    itemSelector: '.col-md-6.col-xl-4',
                    columnWidth: '.col-md-6.col-xl-4',
                    percentPosition: true,
                    isResizeBound: true
                });

            });

            $(".user-image-profile").animate({
                "width": "40px",
                "height": "40px"
            }, 100);
        }
        else{
            $(".menu-name").animate({
                "opacity": "1"
            }, function(){
                $(this).show();
            });

            $(".dashboard-side-menu-container").animate({
                "width": "19.33em"
            }).removeClass("active");

            $(".list-side-menu>li").animate({
                "width": "19.33em"
            });

            $(".dashboard-body-container").animate({
                "margin-left": "19.33em"
            }, function(){
                $('.shipping-address-container').masonry({
                    itemSelector: '.col-md-6.col-xl-4',
                    columnWidth: '.col-md-6.col-xl-4',
                    percentPosition: true,
                    isResizeBound: true
                });
            });

            $(".user-image-profile").animate({
                "width": "183px",
                "height": "183px"
            }, 100);
        }
    });
})(jQuery);
