(function($) {

    var $currentUser = null;
    var $sendMessageForm = $("form[name='send-message']");

    $(".send-message-trigger").on("click", function(){
        var $this = $(this);

        $(".send-message-modal").modal("show");

        $currentUser = $this.attr("data-id");

        $('.coupled').modal({
            allowMultiple: false
        });
    });


    $(document).ready(function(){

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

                if($message != ""){
                    $.ajax({
                        url: Routing.generate('core_send_message'),
                        type: 'POST',
                        data: {recipientId:$currentUser,message:$message,isImage:0},
                        success: function(response) {
                            if(response.isSuccessful){
                                $("textarea[name='message']").val("");
                                $(".send-message-modal").modal("hide");
                                $(".success-send-message").modal("show");
                            }
                        }
                    });
                }

                return false;
            }
        };

        $sendMessageForm.form($formRules);
    });
})(jQuery);