var $socket;
var $token;

(function($) {
 	$(document).ready(function(){
 		var $baseUri = $("[data-base-uri]").data("base-uri");
        if ($baseUri.search('.local') > -1) {
            return;
        }

 		var $data = {};

 		$token = $("[data-token]").data("token");
 		$nodePort = $("[data-node-port]").data("node-port");
 		$socket = io.connect($baseUri + ":" + $nodePort, {secure:true});

 		if($token != null || typeof $token != 'undefined'){
 	        $socket.emit("subscribe socket", $token);
 		}

         $socket.on("update_unread_messages", function(data){
             var $unreadMessages = data.data.unreadMessages;
             var $mobileNav = $(".left-wing-mobile");
             var $messagesCounterDom = $(".unread-messages-badge, .unread-messages-text");

             if($unreadMessages > 0){
                 $messagesCounterDom.show().text(data.data.unreadMessages)
                 $messagesCounterDom.removeClass("hidden");
             }
             else{
                 $messagesCounterDom.hide().text(data.data.unreadMessages)
                 $messagesCounterDom.addClass("hidden");
             }
             
            var $wishlistCount = parseInt($mobileNav.find(".item-counter").text());
            var $messageCount = parseInt($mobileNav.find(".unread-messages-badge").text());

            var $total = $wishlistCount + $messageCount;

            $(".notifications-badge").text($total);

            if($total > 0){
                $(".notifications-badge").show();
            }
            else{
                $(".notifications-badge").hide();
            }

         });
 	});
})(jQuery);
