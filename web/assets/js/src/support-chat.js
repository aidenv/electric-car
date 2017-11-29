(function($) {
    $(document).on("ready", function(){
        $(".trigger-toggle").on("click", function(){
            var $hostname = $(".support-chat-full iframe").data("agent-hostname");
            $(".support-chat-full, .support-chat-compressed").toggleClass("open");
            if($(".support-chat-full").hasClass("open")){
                $(".support-chat-full iframe").attr("src", $hostname + "/#/auth/public/loading");
            }
            else{
                $(".support-chat-full iframe").attr("src", $hostname + "/#/auth/disconnect");
            }
        });
    });

})(jQuery);
