(function($) {
    $(document).on("ready", function(){
        $(".select").on("click", function(){
            $(this).parents(".col-md-4").prependTo(".selected-products>.row");
            $(this).removeClass("blue select").addClass("red remove").text("Remove");
        });
        $(".remove").on("click", function(){
            $(this).parents(".col-md-4").prependTo(".all-products>.row");
            $(this).removeClass("red remove").addClass("blue select").text("Select");
        });
    });
    
})(jQuery);