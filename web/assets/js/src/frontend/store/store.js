(function ($) {
    var $followContainer = $('.follow-container');

    $('body').on('keyup', function(evt) {
        if (evt.keyCode == 27) {
            $('.close-search-form-trigger').click();
        }
        if (evt.keyCode == 13) {
            $('.open-search-form-trigger').click();
            $('.form-large-search').focus();
        }
    });

    $(window).on("load ready resize scroll", function(){ 

        topBannerOffset = $('.top-banner-container').offset().top; 

        if ($(this).scrollTop() > topBannerOffset){ 
            $(".store-menu-header-item .store-menu-header").stop(true).animate({
                bottom: "14px"
            }, 100);

             $(".store-name-header-item .store-name-header").stop(true).animate({
                bottom: "0px"
            }, 100);

            $(".brand-icon-logo").animate({
                bottom: "85px"
            }, 100).hide();

            $(".search-store-header").stop(true).animate({
                bottom: "50px"
            }, 100);
        }
        else{
            $(".store-menu-header-item .store-menu-header").stop(true).animate({
                bottom: "-25px"
            }, 100);

            $(".store-name-header-item .store-name-header").stop(true).animate({
                bottom: "-47px"
            }, 100);

            $(".brand-icon-logo").stop(true).animate({
                bottom: "0px"
            }, 100).show();

            $(".search-store-header").stop(true).animate({
                bottom: "0px"
            }, 100);
        }
    });

    $(document).ready(function(){

        try{
            supplyReferralCode ($('#domain-container').attr('data-value'));
        }
        catch(err){
            
        }
        
        var $sendMessageForm = $("form[name='send-message']");

        var $formRules =  {
            fields : {
                message: {
                    identifier  : 'message',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[1024]',
                            prompt : 'Please enter at most 1024 characters'
                        }
                    ]
                }
            },
            onSuccess: function(){
                var $message = $("textarea[name='message']").val().trim();
                var $currentUser = $sendMessageForm.data("id");

                if($message != ""){
                    $.ajax({
                        url: Routing.generate('core_send_message'),
                        type: 'POST',
                        data: {recipientId:$currentUser,message:$message,isImage:0},
                        beforeSend: function(){
                            applyLoading($sendMessageForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                $("textarea[name='message']").val("");
                                $(".send-message-modal").modal("hide");
                                $(".success-send-message-modal").modal("show");
                            }
                        },
                        complete: function(){
                            unloadButton($sendMessageForm);
                        }
                    });
                }

                return false;
            }
        };

        $sendMessageForm.form($formRules);
    });

    $(".open-search-form-trigger").on("click", function(){
        // $(".store-search-form-container").show().animate({
        //     top: "50px"
        // });
        $(".header-open-search-form-trigger").fadeOut();
        $(".store-search-form-container").addClass("open-store-search");
    });

    $(".close-search-form-trigger").on("click", function(){
        // $(".store-search-form-container").animate({
        //     top: "-300px"
        // });
        $(".header-open-search-form-trigger").fadeIn();
        $(".form-large-search").val("");
        $(".store-search-form-container").removeClass("open-store-search");
    });

    $(".send-message-trigger").on("click", function(){
        $(".send-message-modal").modal("show");

        $('.coupled').modal({
            allowMultiple: false
        });
    });

    $followContainer.on('click', '.btn-follow', function () {
        var $this = $(this);
        var $isFollowing = $('#isFollowing');
        var csrfToken = $("meta[name=csrf-token]").attr("content");

        if (parseInt($isFollowing.val()) === 1) {
            return false;
        }

        $.ajax({
            url: Routing.generate('user_follow'),
            method: 'POST',
            dataType: 'JSON',
            data: {
                sellerId: parseInt($('#sellerId').val().trim()),
                _token: csrfToken
            },
            beforeSend: function () {
                $this.attr('disabled', true);
                $this.find('.follow-text').html('Loading...');
            },
            success: function (response) {
                if (response) {
                    $this.find('.follow-text').html('Unfollow Seller');
                    $this.attr('disabled', false).attr('class', 'btn-un-follow');
                    $isFollowing.val(1);
                }
            }
        });
    });

    $followContainer.on('click', '.btn-un-follow', function () {
        var $this = $(this);
        var $isFollowing = $('#isFollowing');
        var csrfToken = $("meta[name=csrf-token]").attr("content");

        if (parseInt($isFollowing.val()) === 0) {
            return false;
        }

        $.ajax({
            url: Routing.generate('user_un_follow'),
            method: 'POST',
            dataType: 'JSON',
            data: {
                sellerId: parseInt($('#sellerId').val().trim()),
                _token: csrfToken
            },
            beforeSend: function () {
                $this.attr('disabled', true);
                $this.find('.follow-text').html('Loading...');
            },
            success: function (response) {
                if (response) {
                    $this.find('.follow-text').html('Follow Seller');
                    $this.attr('disabled', false).attr('class', 'btn-follow');
                    $isFollowing.val(0);
                }
            }
        });
    });

    $(".store-body-wrapper .box-container").matchHeight();

}(jQuery));
