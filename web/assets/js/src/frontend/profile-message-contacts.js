(function($) {
    var $currentContactPage = 1;
    var $contactExecuting = false;
    var $keyword = "";
    var $timeOut;

    $(window).on("ready load resize", function(){
        var windowHeight = $(window).outerHeight();
        var navbarHeight = $(".navbar").outerHeight();
        var heightOfRightSideTitleBar = $(".right-side-title-bar").outerHeight();
        var heightOfSideNotificationsContainer = $(".dashboard-side-notifications-container").outerHeight();
        var heightOfSearchContactContainer = $(".search-contact-container").outerHeight();
        var heightOfRightSideItems = ((heightOfSideNotificationsContainer - heightOfSearchContactContainer - heightOfRightSideTitleBar * 2)/2)-25;
        var widthOfNotificationDetailsContainer = ($(".list-unstyled.list-notifications a").outerWidth()) - 45;
        var heightOfDashboardBody = windowHeight - navbarHeight;

        $(".dashboard-body-container").css({
            minHeight: heightOfDashboardBody
        });

        $(".notification-description").css({
            maxWidth: widthOfNotificationDetailsContainer
        });

        $(".right-side-item-container").css({
            height: heightOfRightSideItems
        });
    });

    $(window).on("load", function(){
        $(".notifications-container, .contacts-container").mCustomScrollbar();
    });

    $(document).ready(function(){

        var url = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
        var pathArray = window.location.pathname.split( '/' );

        var segment = pathArray[2];

        if(segment !== "messages"){
            $(document).on("click", ".list-contacts .contact", function(){
                var $id = $(this).data("id");
                var reloadUrl = Routing.generate('profile_messages') + "/" + $id;
                window.location.href = reloadUrl;
            });
        }

        refrehContactsScrollbar();

        if (typeof $socket !== 'undefined') {
            $socket.on("update_contacts", function(contact){
                var $li = renderSingleContactLi(contact);
                $(".list-contacts").prepend($li);
            });

            $socket.on("contact_online", function(data){
                $(".list-contacts .contact[data-id='" + data.slug + "'] .status .online-status").removeClass("offline").addClass("online");
                $(".list-contacts .contact[data-id='" + data.slug + "'] .status .online-text").text("Online");
            });
            
            $socket.on("contact_offline", function(data){
                $(".list-contacts .contact[data-id='" + data.slug + "'] .status .online-status").removeClass("online").addClass("offline");
                $(".list-contacts .contact[data-id='" + data.slug + "'] .status .online-text").text("Offline");
            });

            $socket.on("update_message_status", function(data){
                var slug = data.data.senderSlug;

                if($token == data.namespace){
                    $(".contact[data-id='" + slug + "'] .unread-messages-counter").text("0");
                    $(".contact[data-id='" + slug + "'] .unread-messages-counter").hide();
                }
            });
        }

        $(document).on("keyup", "input[name='keyword']", function(){
            getSearchedContacts();
        });

        $(document).on("keydown", "input[name='keyword']", function(){
            clearSearchedContacts();
        }); 
    });

    function getSearchedContacts(){
        $timeOut = setTimeout(function(){
            $currentContactPage = 0;
            $keyword = $("input[name='keyword']").val();

            if(!$contactExecuting){
                $contactExecuting = true;
                updateContactList();
            }

        }, 2000);
    }

    function clearSearchedContacts(){
        clearTimeout($timeOut);

        $(".list-contacts").html("");
    }

    function refrehContactsScrollbar(){
        $(".contacts-container").mCustomScrollbar({
            mouseWheelPixels: 50,
            scrollInertia: 150,
            autoHideScrollbar: false,
            callbacks: {
                onTotalScroll: function(){
                    updateContactList();
                }
            }
        });

        $(".contacts-container").mCustomScrollbar("update");
        $(".contacts-container").mCustomScrollbar("scrollTo", "top");
    }

    function updateContactList(){

        $currentContactPage += 1;

        $.ajax({
            url: Routing.generate('core_get_contacts'),
            type: 'POST',
            data: {keyword:$keyword,page:$currentContactPage,limit:10},
            success: function(response) {
                if(response.isSuccessful){
                    var $contacts = response.data.contacts;
                    var $list = renderContactsLi($contacts);

                    $(".list-contacts").append($list);

                    $contactExecuting = false;
                }
            }
        });
    }

    function renderContactsLi(contacts){
        var $list = "";

        contacts.forEach(function(contact){
            if($(".list-contacts li[data-id='" + contact.slug + "']").length == 0){
                $list += renderSingleContactLi(contact);
            }
        });

        return $list;
    }

    function renderSingleContactLi(contact){
        var $isOnline = contact.isOnline == "1"? "online":"offline";
        var $isOnlineText = contact.isOnline == "1"? "Online":"Offline";
        var $hasUnreadMessage = parseInt(contact.hasUnreadMessage) > 0 ? null : 'style="display: none;"';

        return '<li class="contact" data-id="' + contact.slug + '">' +
                    '<a>' +
                        '<table class="table-seller-name">' +
                            '<thead>' +
                                '<tr>' +
                                    '<td class="td-seller-img">' +
                                        '<div class="img-seller-container">' +
                                            '<img src="' + contact.profileThumbnailImageUrl + '" class="img-buyer"/>' +
                                        '</div>' +
                                    '</td>' +
                                    '<td class="td-seller-name">' +
                                        '<div class="relative">' + 
                                            '<span class="name">' +
                                                '<a>' +
                                                    contact.fullName +
                                                '</a>' +
                                            '</span>' +
                                            '<span class="status">' +
                                                '<i class="icon icon-circle online-status ' + $isOnline + '"></i>' +
                                                '<span class="light-color online-text">' + $isOnlineText + '</span>' +
                                            '</span>' + 
                                            '<span class="unread-messages-counter"' + $hasUnreadMessage + '>' +
                                                parseInt(contact.hasUnreadMessage) +
                                            '</span>' +

                                        '</div>' +
                                    '</td>' +
                                '</tr>' +
                            '</thead>' +
                        '</table>' +
                    '</a>' +
                '</li>';
    }
    
    //Add extra padding for dropdown itemms
    $('.ui.dropdown .item, .ui.selection.dropdown .menu>.message, .category-dropdown').attr('style', 'padding: 0.5em 1.25em !important');
    $('.ui.dropdown .item, .menu>.message').attr('style', 'padding: 1em 1.25em !important');

})(jQuery);
