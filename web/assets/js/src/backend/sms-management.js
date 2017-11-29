(function ($) {

    var sendSmsForm = $("form[name='send-sms']");

    $(".send-message-trigger").on("click", function(){
        $(".send-message-modal").modal("show");
    });

    $(".send-message-modal .send").on("click", function(){
        $(".message-sent-modal").modal("show");
    });

    $(".view-message-trigger").on("click", function(){
        $(".view-message-modal").modal("show");
    });

    $(".delete-message-trigger").on("click", function(){
        $(".delete-message-modal").modal("show");
    });

    $(".delete-message-modal .delete").on("click", function(){
        $(".delete-success-modal").modal("show");
    });
})(jQuery);
