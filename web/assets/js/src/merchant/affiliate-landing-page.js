(function($) {
    smoothScroll.init();

    window.sr= new scrollReveal({
      reset: false,
      move: '10px',
      mobile: false
    });

    $(".video-modal-trigger").on("click", function(){
        $(".video-modal").modal("show");
    });

    function sticky_relocate() {
        var window_top = $(window).scrollTop();
        var div_top = $('.segment.second').offset().top-30;
        // if(window_top < 30){
        //     $('.segment.first header').css({
        //         top: "-100px",

        //     });
        // }
        if(window_top == 0){
            $('.segment.first header').css({
                top: "0px",

            });
        }
        else if (window_top > div_top) {
            $('.segment.first header').addClass('is_stuck');
            $('.segment.first header').animate({
                top: "0px"
            });
        } else {
            $('.segment.first header').removeClass('is_stuck');
        }
    }

    $(window).on("load scroll", sticky_relocate);
    sticky_relocate();

    $(".scroll-spy-pager").stick_in_parent({
        parent: ".stick-side-menu-wrapper",
        offset_top: 110
    })

    // Cache selectors
    var lastId,
        topMenu = $(".scroll-spy-pager"),
        topMenuHeight = topMenu.outerHeight()+15,
    // All list items
    menuItems = topMenu.find("a"),
    // Anchors corresponding to menu items
    scrollItems = menuItems.map(function(){
        var item = $($(this).attr("data-href"));
        if (item.length) { return item; }
    });

    // Bind to scroll
    $(window).on("load resize scroll", function(){
        // Get container scroll position
        var fromTop = $(this).scrollTop()+topMenuHeight;

        // Get id of current scroll item
        var cur = scrollItems.map(function(){
            if ($(this).offset().top < fromTop)
                return this;
        });

        // Get the id of the current element
        cur = cur[cur.length-1];
        var id = cur && cur.length ? cur[0].id : "";

        if (lastId !== id) {
            lastId = id;
            // Set/remove active class
            menuItems.parent().removeClass("active")
            .end().filter("[data-href=#"+id+"]").parent().addClass("active");
        }
    });
})(jQuery);