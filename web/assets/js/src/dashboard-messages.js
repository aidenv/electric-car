(function($) {
    var $messageAjaxExecuting = false;
    var $consversationHeadAjaxExecuting = false;
    var $currentConversationPage = 1;
    var $currentConversationHeadPage = 1;
    var $currentUser = null;
    var $userId = 0;
    var $timeOut;
    var $acceptedTypes = ["image/jpg", "image/jpeg", "image/png"];
    var $hasUnreadThread = false;
    var $currentTimeSent = moment().format("YYYY-MM-DD HH:mm:ss");
    var recentMessageContainerHeight, 
        threadContainerHeight, 
        mainThreadContainerHeight,
        windowHeight,
        navbarHeight,
        submenuHeight;

    // $(window).on("resize", function(){
    //     getDimensions();
    //     refreshPageCSS(recentMessageContainerHeight, threadContainerHeight, mainThreadContainerHeight);
    // });

    $(window).load(function(){

        var $recentMessages = $(".recent-messages-ul li");
        var $preloadedUser = $(".dashboard-message-container[data-user-id]").data("user-id");
        
        if($preloadedUser != null && $preloadedUser != ""){
            $userId = $preloadedUser;
        }

        // refreshPageCSS(recentMessageContainerHeight, threadContainerHeight, mainThreadContainerHeight);
        refreshRecentMessagesScrollbar();

        $socket.on("update_message", function(message){
            var $li = "";
            var $messageContainerList = $(".messages-container li");

            if($currentUser == message.data.slug){
                if(message.data.isImage == "0"){ 
                    $li = renderSenderTextLi(message.data);
                }
                else{
                    $li = renderSenderImageLi(message.data);
                }

                $hasUnreadThread = true;
            }
            else if($token == message.data.senderRoom){
                if(message.data.isImage == "0"){
                    $li = renderRecipientTextLi(message.data);
                }
                else{
                    $li = renderRecipientImageLi(message.data);
                }
            }

            $(".messages-container").append($li);

            $('.message-gallery').magnificPopup({
                delegate: 'a',
                type: 'image',
                tLoading: 'Loading image #%curr%...',
                mainClass: 'mfp-img-mobile',
                gallery: {
                    enabled: true,
                    navigateByImgClick: true,
                    preload: [0,1] // Will preload 0 - before current, and 1 after the current image
                },
                image: {
                    tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
                }
            });
        });

        $socket.on("resort_head", function(message){
            var $recentMessage = $(".recent-messages-ul li[data-id='" + message.slug + "']");

            var $date = moment(message.lastMessageDate).format("MM/DD/YYYY");
            var $isOnline = message.isOnline == "1"? "online":"offline";
            var $message = message.message.length > 64? message.message.slice(1, 64) + '...' : message.message;


            var $li = renderSingleHeadLi(message, $message, $date, $isOnline);
            
            $(".empty-messages").addClass("hidden");

            if($recentMessage.length > 0){
                $recentMessage.remove();
            }

            $(".recent-messages-ul").prepend($li);

            $(".recent-messages").removeClass("hidden");
        });

        $socket.on("update_message_status", function(data){
            var recipient = data.data.recipient;

            if(recipient == $currentUser || $token == data.namespace){
                $(".inbound-message .message-status .is-seen").addClass("hidden");
                $(".inbound-message:last .message-status .is-seen").removeClass("hidden");
            }
        });

        $socket.on("contact_online", function(data){
            $(".recent-messages-ul .message-conversation-head[data-id='" + data.slug + "'] .image-status-container .online-status").removeClass("offline").addClass("online");
            $(".conversation-view[data-id='" + data.slug + "'] .status .online-status").removeClass("offline").addClass("online");
            $(".conversation-view[data-id='" + data.slug + "'] .status .online-text").text("Online");
        });
        
        $socket.on("contact_offline", function(data){
            $(".recent-messages-ul .message-conversation-head[data-id='" + data.slug + "'] .image-status-container .online-status").removeClass("online").addClass("offline");
            $(".conversation-view[data-id='" + data.slug + "'] .status .online-status").removeClass("online").addClass("offline");
            $(".conversation-view[data-id='" + data.slug + "'] .status .online-text").text("Offline");
        });

        if($userId == 0){
            var $dataId = $($recentMessages[0]).attr("data-id");
            renderConversationThread($dataId);
        }
        else if($userId != 0){
            renderConversationThread($userId);
        }

        $(document).on("click", ".attachment-trigger", function(){
            $("input[name='messageImages']").trigger("click");
        });

        $(document).on("change", "input[name='messageImages']", function(){
            var $this = $(this);
            var $files = $this[0].files;
            var $filesToUpload = [];

            if($files.length > 0){
                var $fileCount = $files.length;
                for(var $i = 0; $i < $fileCount; $i++){
                    var $file = $files[$i];
                    var $mimeType = $file.type;
                    var $isInArray = jQuery.inArray($mimeType, $acceptedTypes) != -1? true : false;

                    if(!$isInArray){
                        $this.val("");
                        $(".invalid-image").modal("show");
                        return;
                    }

                    $filesToUpload.push($file);
                }


                uploadMessageImages($filesToUpload);
            }
        });

        $(document).on("click", ".message-conversation-head, .contact",function(){
            $userId = $(this).attr("data-id");

            if($currentUser != $userId){
                // getDimensions();
                // refreshPageCSS(400, threadContainerHeight, mainThreadContainerHeight);
                renderConversationThread($userId);
                $(".message-conversation-head[data-id='" + $userId + "']>a").removeClass("unread");
            }
            
            $(".col-body-message.conversation").addClass("thread-open");
            $(".col-body-message.conversation").removeClass("contacts-open");
        });

        $(document).on("click", ".delete-thread", function(){
            $(".delete-conversation-thread").modal({
                onApprove: function(){
                    deleteConversation();
                    $(".col-body-message.conversation").removeClass("open");
                }
            }).modal("show");
        });

        $(document).on("click", ".send-message-button", function(){
            sendMessage();
        });

        $(document).on("click", "#conversation-container", function(){
            if($hasUnreadThread){
                $.ajax({
                    url: Routing.generate('core_set_conversation_as_read'),
                    type: 'POST',
                    data: {userId:$currentUser},
                    success: function(response) {
                        if(response.isSuccessful){
                            $hasUnreadThread = false;
                            $(".message-conversation-head[data-id='" + $currentUser + "']>a").removeClass("unread");
                        }
                    }
                });
            }
        });

        $(document).on("click", ".message-conversation-head", function(){
            $(".col-body-message.conversation").addClass("open");
        });

        $(document).on("click", ".back-recent-messages", function(){
            $(".col-body-message.conversation").removeClass("open");
        });
    });

    function uploadMessageImages($files){
        var $formData = new FormData();

        $formData.append("recipientId", $currentUser);
        $files.forEach(function($file){
            $formData.append("messageImages[]", $file);
        });

        $.ajax({
            url: Routing.generate('core_upload_mutiple_message_image'),
            type: 'POST',
            data: $formData,
            processData: false,
            contentType: false,
            beforeSend: function(){
                $(".thread-loader").removeClass("hidden");
            },
            success: function(response) {
                if(response.isSuccessful){
                    $("input[name='messageImages']").val('');
                }
            },
            errors: function(){
                $(".invalid-image").modal("show");
            },
            complete: function(){
                $(".thread-loader").addClass("hidden");
            }
        });
    }

    function renderConversationThread($userId){
        
        $.ajax({
            url: Routing.generate('core_get_active_conversation'),
            type: 'POST',
            data: {userId:$userId},
            beforeSend: function(){
                $(".thread-loader").removeClass("hidden");
            },
            success: function(response) {
                if(response.isSuccessful){
                    var $template = response.data.template;

                    $currentConversationPage = 1;
                    $stashedMessageCount = 1;
                    $currentUser = $userId;
                    
                    $("#conversation-container").html($template);
                    $(".message-conversation-head a").removeClass("active");
                    $(".message-conversation-head[data-id='" + $userId + "'] > a").addClass("active");

                    $(window).trigger('resize');

                    var room = $(".conversation-view").attr("data-room");

                    $socket.emit("join room", room);

                    $(".recent-messages, .conversation").removeClass("hidden");
                    $(".empty-messages").addClass("hidden");

                    $('.message-gallery').magnificPopup({
                        delegate: 'a',
                        type: 'image',
                        tLoading: 'Loading image #%curr%...',
                        mainClass: 'mfp-img-mobile',
                        gallery: {
                            enabled: true,
                            navigateByImgClick: true,
                            preload: [0,1] // Will preload 0 - before current, and 1 after the current image
                        },
                        image: {
                            tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
                        }
                    });

                    $(".inbound-message .message-status .is-seen").addClass("hidden");
                    $(".inbound-message .message-status .is-seen[data-is-seen='1']:last").removeClass("hidden");
                    
                    $hasUnreadThread = false;
                    refreshConversationScrollbar();
                }
            },
            complete: function(){
                $currentTimeSent = moment().format("YYYY-MM-DD HH:mm:ss");
                $(".thread-loader").addClass("hidden");
            }
        });
    }

    function sendMessage(){
        var $message = $("textarea[name='message']").val().trim();
        var $button = $(".send-message-button");

        if($message != ""){
            $.ajax({
                url: Routing.generate('core_send_message'),
                type: 'POST',
                data: {recipientId:$currentUser,message:$message,isImage:0},
                beforeSend: function(){
                    $button.attr('disabled', true);
                    $button.find(".text").hide();
                    $button.find(".loader").show();
                },
                success: function(response) {
                    if(response.isSuccessful){
                        $("textarea[name='message']").val("");
                    }
                },
                complete: function(){
                    $button.attr('disabled', false);
                    $button.find(".text").show();
                    $button.find(".loader").hide();
                }
            });
        }
    }

    function refreshPageCSS(recentMessageContainerHeight, threadContainerHeight, mainThreadContainerHeight){
        $(".recent-messages-container ").css({
            height: recentMessageContainerHeight
        });

        $(".thread-container-wrapper").css({
            height: threadContainerHeight
        });

        $("#conversation-container").css({
            height: mainThreadContainerHeight
        });
    }

    function refreshConversationScrollbar(){
        $(".thread-container-wrapper").mCustomScrollbar({
            mouseWheelPixels: 50,
            scrollInertia: 150,
            autoHideScrollbar: false,
            callbacks: {
                onTotalScrollBack: function(){
                    updateMessagesList();
                }
            }
        });

        $(".thread-container-wrapper").mCustomScrollbar("update");
        $(".thread-container-wrapper").mCustomScrollbar("scrollTo", "bottom");
    }

    function refreshRecentMessagesScrollbar(){
        $(".recent-messages-container").mCustomScrollbar({
            mouseWheelPixels: 50,
            scrollInertia: 150,
            autoHideScrollbar: false,
            callbacks: {
                onTotalScroll: function(){
                    updateConversationHead();
                }
            }
        });

        $(".recent-messages-container").mCustomScrollbar("update");
        $(".recent-messages-container").mCustomScrollbar("scrollTo", "bottom");
    }

    function updateMessagesList(){

        if(!$messageAjaxExecuting){
            var $userId = $(".messages-container").attr("data-id");
            $messageAjaxExecuting = true;
            $currentConversationPage += 1;

            $.ajax({
                url: Routing.generate('core_get_conversation_messages'),
                type: 'POST',
                data: {userId:$userId,page:$currentConversationPage,limit:10,excludedTimeSent:$currentTimeSent},
                success: function(response) {
                    if(response.isSuccessful){
                        var $messages = response.data.messages;
                        var $messageCheckpoint = $("li[data-message-id]").first().attr("data-message-id");
                        var $list = renderMessageTemplate($messages);

                        $(".messages-container").prepend($list);
                        $(".thread-container-wrapper").mCustomScrollbar("scrollTo", $("li[data-message-id='" + $messageCheckpoint + "']"));
                    }
                }
            });

            $messageAjaxExecuting = false;
        }
    }

    function deleteConversation(){

        $.ajax({
            url: Routing.generate('core_delete_conversation'),
            type: 'POST',
            data: {userId:$currentUser},
            success: function(response) {
                if(response.isSuccessful){
                    $("#conversation-container").html("");
                    $(".message-conversation-head[data-id='" + $currentUser + "']").remove();
                    $currentUser = 0;

                    if($(".message-conversation-head").length < 1){
                        $(".recent-messages, .conversation").addClass("hidden");
                        $(".empty-messages").removeClass("hidden");
                    }
                }
            }
        });
    }

    function updateConversationHead(){

        if(!$consversationHeadAjaxExecuting){
            $consversationHeadAjaxExecuting = true;
            $currentConversationHeadPage += 1;

            $.ajax({
                url: Routing.generate('core_get_conversation_head'),
                type: 'POST',
                data: {page:$currentConversationHeadPage,limit:10},
                success: function(response) {
                    if(response.isSuccessful){
                        var $messages = response.data.messages;
                        var $list = renderConversationHeadLi($messages);

                        $(".recent-messages-ul").append($list);
                    }
                }
            });

            $consversationHeadAjaxExecuting = false;
        }
    }

    function renderConversationHeadLi(messages){
        var $list = "";

        messages.forEach(function(messageDetails){
            var $date = moment(messageDetails.lastMessageDate).format("MM/DD/YYYY");
            var $isOnline = messageDetails.isOnline == "1"? "online":"offline";
            var $message = messageDetails.message.length > 64? messageDetails.message.slice(1, 64) + '...' : messageDetails.message;

            $list += renderSingleHeadLi(messageDetails, $message, $date, $isOnline);
        });

        return $list;
    }

    function renderSingleHeadLi(messageDetails, $message, $date, $isOnline){
        var messageContent;
        var classes = [];
        var hasUnreadMessage = parseInt(messageDetails.hasUnreadMessage) > 0? 'class="unread"':'';
        var userId = messageDetails.slug;

        if(parseInt(messageDetails.hasUnreadMessage) > 0){
            classes.push("unread");
        }
        

        if($currentUser == userId){
            classes.push("active");
        }

        if(messageDetails.isImage == "0"){
            messageContent = $message;
        }
        else{
            if(messageDetails.userId == messageDetails.senderUid){
                messageContent = messageDetails.fullName + " sent a photo.";
            }
            else{
                messageContent = "You sent a photo."
            }
        }

        return '<li class="message-conversation-head" data-id="' + messageDetails.slug + '">' +
                    '<a class="' + classes.join(" ") + '">' +
                        '<table class="table-message-item">' +
                            '<tbody>' +
                                '<tr>' +
                                    '<td width>' +
                                        '<div class="image-status-container">' +
                                            '<div class="image-holder img-recent-message-sender">' +
                                                '<img src="' + messageDetails.profileThumbnailImageUrl + '" alt="" class="img-auto-place"/>' +
                                            '</div>' +
                                            '<i class="icon icon-circle online-status ' + $isOnline + '"></i>' +
                                        '</div>' +
                                    '</td>' +
                                    '<td class="td-sender-name">' +
                                        '<span>' + messageDetails.fullName + '</span>' +
                                    '</td>' +
                                    '<td class="td-time">' +
                                        '<span class="pull-right">' + $date + '</span>' + 
                                    '</td>' +
                                '</tr>' +
                                '<tr>' +
                                    '<td colspan="3" class="td-message-synopsis light-color">' +
                                        messageContent +
                                    '</td>' +
                                '</tr>' +
                            '</tbody>' +
                        '</table>' +
                    '</a>' +
                '</li>';
    }

    function renderMessageTemplate(messages){
        $listHtml = "";

        messages.forEach(function(message){
            if(message.senderId === $userId){
                if(message.isImage == "0"){
                    $listHtml += renderSenderTextLi(message);
                }
                else{

                }
            }
            else{
                if(message.isImage == "0"){
                    $listHtml += renderRecipientTextLi(message);
                }
                else{
                    
                }
            }
        });

        return $listHtml;
    }

    function renderSenderImageLi(message){

        var date = moment(message.timeSent).format("MM/DD/YYYY");

        var $li = '' +
        '<li class="outbound-message" data-message-id="' + message.messageId + '">' +
            '<div class="row">' +
                '<div class="col-md-12 col-xs-12">' +
                    '<table class="table-thread-item">' +
                        '<tbody>' +
                            '<tr>' + 
                                '<td class="td-sender-image-container">' + 
                                    '<div class="image-holder img-thread-message-sender">' + 
                                        '<img src="' + message.senderProfileThumbnailImageUrl + '" alt="" class="img-auto-place"/>' +
                                    '</div>' +
                                '</td>' +
                                '<td class="td-message-container">' + 
                                    '<div class="message">' + 
                                        '<div class="message-gallery">' +
                                            '<a href="' + message.message + '">' +
                                                '<img src="' + message.message + '" width="75" height="75">' +
                                            '</a>' +
                                        '</div>' +
                                    '</div>' + 
                                    '<div class="message-status">' +
                                        '<span class="status-item">' +
                                            '<i class="icon icon-clock"></i>' + date +
                                        '</span>' +
                                        '<span class="status-item is-seen hidden">' +
                                            '<i class="icon icon-check"></i> Seen' +
                                        '</span>' +
                                    '</div>' +
                                '</td>' +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>' +
        '</li>';

        return $li;
    }

    function renderSenderTextLi(message){

        var date = moment(message.timeSent).format("MM/DD/YYYY");

        var $li = '' +
        '<li class="outbound-message" data-message-id="' + message.messageId + '">' +
            '<div class="row">' +
                '<div class="col-md-12 col-xs-12">' +
                    '<table class="table-thread-item">' +
                        '<tbody>' +
                            '<tr>' + 
                                '<td class="td-sender-image-container">' + 
                                    '<div class="image-holder img-thread-message-sender">' + 
                                        '<img src="' + message.senderProfileThumbnailImageUrl + '" alt="" class="img-auto-place"/>' +
                                    '</div>' +
                                '</td>' +
                                '<td class="td-message-container">' + 
                                    '<div class="message">' + message.message + '</div>' + 
                                    '<div class="message-status">' +
                                        '<span class="status-item">' +
                                            '<i class="icon icon-clock"></i>' + date +
                                        '</span>' +
                                        '<span class="status-item is-seen hidden">' +
                                            '<i class="icon icon-check"></i> Seen' +
                                        '</span>' +
                                    '</div>' +
                                '</td>' +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>' +
        '</li>';

        return $li;
    }

    function renderRecipientImageLi(message){

        var date = moment(message.timeSent).format("MM/DD/YYYY");

        var $isSeen = message.isSeen == "1"? '<span class="status-item is-seen">' +
                                                '<i class="icon icon-check"></i> Seen' +
                                            '</span>' : 
                                            '<span class="status-item is-seen hidden">' +
                                                '<i class="icon icon-check"></i> Seen' +
                                            '</span>';

        var $li = '' +
        '<li class="inbound-message" data-message-id="' + message.messageId + '">' +
            '<div class="row">' +
                '<div class="col-md-12 col-xs-12">' +
                    '<table class="table-thread-item">' +
                        '<tbody>' +
                            '<tr>' +
                                '<td class="td-message-container">' +
                                    '<div class="message">' + 
                                        '<div class="message-gallery">' +
                                            '<a href="' + message.message + '">' +
                                                '<img src="' + message.message + '" width="75" height="75">' +
                                            '</a>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="message-status">' +
                                        '<span class="status-item">' +
                                            '<i class="icon icon-clock"></i>' + date +
                                        '</span>' +
                                        $isSeen +
                                    '</div>' +
                                '</td>' +
                                '<td class="td-sender-image-container">' +
                                    '<div class="image-holder img-thread-message-sender">' +
                                        '<img src="' + message.senderProfileThumbnailImageUrl + '" alt="" class="img-auto-place"/>' +
                                    '</div>' +
                                '</td>' +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>' +
        '</li>';

        return $li;
    }

    function renderRecipientTextLi(message){

        var date = moment(message.timeSent).format("MM/DD/YYYY");

        var $isSeen = message.isSeen == "1"? '<span class="status-item is-seen">' +
                                                '<i class="icon icon-check"></i> Seen' +
                                            '</span>' : 
                                            '<span class="status-item is-seen hidden">' +
                                                '<i class="icon icon-check"></i> Seen' +
                                            '</span>';

        var $li = '' +
        '<li class="inbound-message" data-message-id="' + message.messageId + '">' +
            '<div class="row">' +
                '<div class="col-md-12 col-xs-12">' +
                    '<table class="table-thread-item">' +
                        '<tbody>' +
                            '<tr>' +
                                '<td class="td-message-container">' +
                                    '<div class="message">' + message.message + '</div>' +
                                    '<div class="message-status">' +
                                        '<span class="status-item">' +
                                            '<i class="icon icon-clock"></i>' + date +
                                        '</span>' +
                                        $isSeen +
                                    '</div>' +
                                '</td>' +
                                '<td class="td-sender-image-container">' +
                                    '<div class="image-holder img-thread-message-sender">' +
                                        '<img src="' + message.senderProfileThumbnailImageUrl + '" alt="" class="img-auto-place"/>' +
                                    '</div>' +
                                '</td>' +
                            '</tr>' +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>' +
        '</li>';

        return $li;
    }

    function getDimensions(){
        windowHeight = $(window).outerHeight();
        navbarHeight = $(".navbar").outerHeight();
        submenuHeight = 62;

        recentMessageContainerHeight = (windowHeight - navbarHeight) - submenuHeight;
        threadContainerHeight = windowHeight - navbarHeight - submenuHeight - 50;
        mainThreadContainerHeight = windowHeight - navbarHeight;
    }

    $(document).on("ready",function() {
        $('.message-gallery').magnificPopup({
            delegate: 'a',
            type: 'image',
            tLoading: 'Loading image #%curr%...',
            mainClass: 'mfp-img-mobile',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0,1] // Will preload 0 - before current, and 1 after the current image
            },
            image: {
                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
            }
        });
    });

    $(document).on("click", ".message-conversation-head", function(){
        $(".col-body-message.conversation").addClass("thread-open");
        $(".col-body-message.conversation").removeClass("contacts-open");
    });

    $(document).on("click", ".back-recent-messages", function(){
        $(".col-body-message.conversation").removeClass("thread-open");
    });

    $(document).on("click", ".mobile-contact-trigger", function(){
        $(".col-body-message.conversation").addClass("contacts-open");
    });

    $(document).on("click", ".back-recent", function(){
        $(".col-body-message.conversation").removeClass("contacts-open");
    });

})(jQuery);
