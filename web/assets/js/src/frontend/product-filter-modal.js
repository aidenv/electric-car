(function ($) {
    $(".filter-modal-trigger").on("click", function(){
        $(".filter-modal").dimmer({
            opacity: 1,
            onShow: function(){
                $("body").css({
                    "overflow": "hidden"
                });
            },
            onHide: function(){
                $("body").css({
                    "overflow": "auto"
                });
            }
        }).dimmer("toggle");
    });

    $(".sort-modal-trigger").on("click", function(){
        $(".sort-modal").dimmer({
            opacity: 1,
            onShow: function(){
                $("body").css({
                    "overflow": "hidden"
                });
            },
            onHide: function(){
                $("body").css({
                    "overflow": "auto"
                });
            }
        }).dimmer("toggle");
    });

    $(".view-by-modal-trigger").on("click", function(){
        $(".view-by-modal").dimmer({
            opacity: 1,
            onShow: function(){
                $("body").css({
                    "overflow": "hidden"
                });
            },
            onHide: function(){
                $("body").css({
                    "overflow": "auto"
                });
            }
        }).dimmer("toggle");
    });

    $(".ui.dimmer.page .close").on("click", function(){
         $(".filter-modal").dimmer("hide");
         $(".sort-modal").dimmer("hide");
    });

    $(".grid-view-mobile-trigger").on("click", function(){
        $(".grid-view-trigger").trigger("click");
        $(".view-by-modal").dimmer("toggle");
    });

    $(".list-view-mobile-trigger").on("click", function(){
        $(".list-view-trigger").trigger("click");
        $(".view-by-modal").dimmer("toggle");
    });
}(jQuery));