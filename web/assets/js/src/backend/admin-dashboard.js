(function ($) {
    $(document).ready(function(){

        //datepicker
        $('.datetimepicker').datetimepicker({
            format: "MM/DD/YYYY (hh:mm:ss)"
        });
    });

    $(window).on("ready load resize", function(){
        var windowWidth = $(this).width();
        var sliderWrapperHeight = $(".slider.wrapper").outerHeight();
        var gridFeaturedItemsHeight = $(".grid-featured-items").outerHeight();
        $(".grid-featured-items").css({
            height: sliderWrapperHeight
        });
        $(".grid.extra").css({
            height: gridFeaturedItemsHeight
        });
    });

    //Multiple selection select box with tokens
    $(".multiple.search.selection").dropdown({
        maxSelections: 5,
        allowAdditions: true
    });

    //Modal
    $(".modal-order-one-trigger").click(function(){
        $('.modal-order-one').modal('show').modal({ blurring: true });
    });

    $(".modal-order-two-trigger").click(function(){
        $('.modal-order-two').modal('show').modal({ blurring: true });
    });

    $(".modal-seller-payout-one-trigger").click(function(){
        $('.modal-seller-payout-one').modal('show').modal({ blurring: true });
    });

    $(".modal-seller-payout-two-trigger").click(function(){
        $('.modal-seller-payout-two').modal('show').modal({ blurring: true });
    });

    $(".modal-buyer-refund-one-trigger").click(function(){
        $('.modal-buyer-refund-one').modal('show').modal({ blurring: true });
    });

    $(".modal-buyer-refund-two-trigger").click(function(){
        $('.modal-buyer-refund-two').modal('show').modal({ blurring: true });
    });

    $(".modal-contact-buyer-seller-one-trigger").click(function(){
        $('.modal-contact-buyer-seller-one').modal('show').modal({ blurring: true });
    });

    $(".modal-register-user-one-trigger").click(function(){
        $('.modal-register-user-one').modal('show').modal({ blurring: true });
    });

    $(".modal-category-listing-one-trigger").click(function(){
        $('.modal-category-listing-one').modal('show').modal({ blurring: true });
    });

    $(".modal-alert-success-trigger").click(function(){
        $('.modal-alert-success').modal('show').modal({ blurring: true });
    });

    $(".modal-promo-trigger").click(function(){
        $('.modal-promo').modal('show').modal({ blurring: true });
    });

    $(".modal-promo-add-trigger").click(function(){
        $('.modal-promo-add').modal('show').modal({ blurring: true });
    });

    $(".modal-cancel-buyer-transaction-trigger").click(function(){
        $('.modal-cancel-buyer-transaction').modal('show').modal({ blurring: true });
    });

    $(".modal-transaction-flagged-trigger").click(function(){
        $('.modal-transaction-flagged').modal('show').modal({ blurring: true });
    });

    $(".modal-transaction-cancelled-trigger").click(function(){
        $('.modal-transaction-cancelled').modal('show').modal({ blurring: true });
    });

    $(".modal-transaction-cancelled-remarks-trigger").click(function(){
        $('.modal-transaction-cancelled-remarks').modal('show').modal({ blurring: true });
    });

    $(".modal-transaction-dispute-trigger").click(function(){
        $('.modal-transaction-dispute').modal('show').modal({ blurring: true });
    });

    $(".modal-transaction-pickup-trigger").click(function(){
        $('.modal-transaction-pickup').modal('show').modal({ blurring: true });
    });

    $(".modal-resolution-approve-trigger").click(function(){
        $('.modal-resolution-approve').modal('show').modal({ blurring: true });
    });

    $(".modal-resolution-reject-trigger").click(function(){
        $('.modal-resolution-reject').modal('show').modal({ blurring: true });
    });

    $(".modal-remarks-approved-trigger").click(function(){
        $('.modal-remarks-approved').modal('show').modal({ blurring: true });
    });

    $(".modal-remarks-reject-trigger").click(function(){
        $('.modal-remarks-reject').modal('show').modal({ blurring: true });
    });

    $(".modal-product-listing-trigger").click(function(){
        $('.modal-product-listing').modal('show').modal({ blurring: true });
    });

    $(".modal-notes-new-trigger").click(function(){
        $('.modal-notes-new').modal('show').modal({ blurring: true });
    });

    $(".modal-notes-view-trigger").click(function(){
        $('.modal-notes-view').modal('show').modal({ blurring: true });
    });

    $(".modal-sales-trigger").click(function(){
        $('.modal-sales').modal('show').modal({ blurring: true });
    });

    $(".modal-create-product-trigger").click(function(){
        $('.modal-create-product').modal('show').modal({ blurring: true });
    });

    $(".modal-create-banner-trigger").click(function(){
        $('.modal-create-banner').modal('show').modal({ blurring: true });
    });

    $(".modal-create-stores-trigger").click(function(){
        $('.modal-create-stores').modal('show').modal({ blurring: true });
    });

    $(".modal-create-category-trigger").click(function(){
        $('.modal-create-category').modal('show').modal({ blurring: true });
    });

    $(".modal-payout-nope-trigger").click(function(){
        $('.modal-payout-nope').modal('show').modal({ blurring: true });
    });

    $(".modal-payout-with-trigger").click(function(){
        $('.modal-payout-with').modal('show').modal({ blurring: true });
    });

    $(".type-1-trigger").click(function(){
        $('.type-1').removeClass('hidden');
        $('.type-2').addClass('hidden');
    });

    $(".type-2-trigger").click(function(){
        $('.type-2').removeClass('hidden');
        $('.type-1').addClass('hidden');
    });

    $(".type-3-trigger").click(function(){
        $('.type-3').removeClass('hidden');
        $('.type-4').addClass('hidden');
    });

    $(".type-4-trigger").click(function(){
        $('.type-4').removeClass('hidden');
        $('.type-3').addClass('hidden');
    });

    $(".samp-trig-add").click(function(){
        $('.sample-bank-add').removeClass('hidden');
        $('.sample-bank-edit').addClass('hidden');
        $('.sample-bank-blank').addClass('hidden');
    });

    $(".samp-trig-edit").click(function(){
        $('.sample-bank-edit').removeClass('hidden');
        $('.sample-bank-add').addClass('hidden');
    });

    $('.slick').slick({
      infinite: false,
      speed: 300,
      slidesToShow: 1,
      variableWidth: true,
      arrows: false
    });

    $('.slick-prev').click(function(){
      $('.slick').slick("slickPrev");
    })

    $('.slick-next').click(function(){
      $('.slick').slick("slickNext");
    })

    $("a.toggle-me").click(function() {
      $( "div.toggle-me" ).toggle();
    });

    //Tabs
    $('.tabular.menu .item').tab();

    $('.modal .tabular.menu .item').tab();

    $(".requestor").dropdown({
      onChange: function (val) {
          $('.cancellation-reason').removeClass('disabled');
      }
    });

    $(".expander").click(function () {
        $header = $(this);
        //getting the next element
        $content = $header.next();
        //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
        $content.slideToggle(500, function () {
            //execute this after slideToggle is done
            //change text of header based on visibility of content div
            $header.text(function () {
                //change text based on condition
                return $content.is(":visible") ? "-" : "+";
            });
        });

    });

})(jQuery);
