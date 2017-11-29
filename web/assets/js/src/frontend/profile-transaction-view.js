(function($) {

    $(".feedback-modal-trigger").click(function(){
        $(".feedback-modal").modal("show");

        $('.coupled').modal('setting', 'allowMultiple', false);     
    });

    $(".feedback-product-modal-trigger").click(function(){
        $(".feedback-product-modal").modal("show");

        $('.coupled-feedback-product').modal('setting', 'allowMultiple', false);
    });

    $(".feedback-rating-container .icon").mouseover(function(){
        if (!$(this).parents('.feedback-rating-container').hasClass('disabled')) {
            $(this).addClass("pre-active");  
            $( this ).prevAll().addClass("pre-active"); 
        }
    });
    
    
    $( ".feedback-rating-container .icon" ).mouseout(function() {
        if (!$(this).parents('.feedback-rating-container').hasClass('disabled')) {
            $(this).removeClass("pre-active");
            $( this ).prevAll().removeClass("pre-active");
        }
    });

    $( ".feedback-rating-container .icon" ).click(function() {
        if (!$(this).parents('.feedback-rating-container').hasClass('disabled')) {
            $(this).addClass("active"); 
            $(this).prevAll().addClass("active"); 
            $(this).nextAll().removeClass("active");
        }
    });
})(jQuery);
