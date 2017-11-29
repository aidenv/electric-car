function sticky_relocate() {
    var window_top = $(window).scrollTop();
    var footer_top = $(".footer-wrapper").offset().top;
    var div_top = $('.sticky-anchor').offset().top;
    var div_height = $(".sticky").height();

    if (window_top + div_height > footer_top)
        $('.sticky').removeClass('stick');
    else if (window_top > div_top) {
        $('.sticky').addClass('stick');
    } else {
        $('.sticky').removeClass('stick');
    }
}

$(function () {
    $(window).scroll(sticky_relocate);
    sticky_relocate();
});
