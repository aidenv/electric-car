(function($) {
    $('.ui.accordion').accordion();

    $(".ui.dropdown").dropdown();

    $('.circle-status').circleProgress({
        size: 160,
        thickness: 20,
        fill: {
            gradient: ["#75338a", "#54b6a7"]
        }
    });

    //Show Comments
    $(".show-all-comments-trigger").click(function(){
        $("#modal-show-all-comments").modal("show");
    });

    //Social Media
    $(".add-social-media").click(function(){
        $('.bordered-segment .form').append('<input type="text" class="form-ui" placeholder="Social Media">');
    });

    //Delete modal
    $(".store-address-segment .delete").click(function(){
        $(".delete-address-modal").modal("show");
    });

    //Delete modal
    $(".add-product-category-trigger").click(function(){
        $(".add-product-category").modal("show");
    });

    //Email Address modal
   $(".verify-email-modal-trigger").click(function(){
       $(".verify-email-modal").modal("show");

       // Open modal when click the "Okay" button from the edit mobile number modal
       $('.success-verify-account').modal('attach events', '.verify-email-modal .submit-to-success');
   });

     //New Address modal
    $(".new-address-modal-trigger").click(function(){
        $(".new-address-modal").modal("show");

        $('.coupled-new-address').modal({
            allowMultiple: false
        });

        // Open modal when click the "Okay" button from the edit mobile number modal
        $('.success-new-address-message').modal('attach events', '.new-address-modal .submit-to-success');
    });

    $('.sub-progress-bar')
      .progress({
        text: {
          active  : '{value} of {total}'
      }
    });

})(jQuery);
