(function ($) {

    var $ajaxLoading = false, $isCreate = false, $deviceNotificationId = null;

    var $frontendHostName = $("[data-frontend-hostname]").data("frontend-hostname");

    var RECIPIENT_ALL = 0,
        RECIPIENT_ANDROID = 1,
        RECIPIENT_IOS = 2;

    var TARGET_TYPE_HOME = "home",
        TARGET_TYPE_WEBVIEW = "webView",
        TARGET_TYPE_PRODUCT = "product",
        TARGET_TYPE_PRODUCT_LIST = "productList",
        TARGET_TYPE_STORE = "seller",
        TARGET_TYPE_STORE_LIST = "sellerList";

    $.fn.form.settings.rules.checkAtleast = function($itemCount, $requirement){
        var $checkboxes = $("input[name='recipient[]']");
        var $checked = 0;

        $checkboxes.each(function($index, $checkbox){
            if($checkbox.checked){
                $checked++;
            }
        });

        if($checked >= parseInt($requirement)){
            return true;
        }

        return false;
    };


    $.fn.form.settings.rules.greaterThanNow = function(){
        var $dateSet = moment($("input[name='dateScheduled']").val(), "MM/DD/YYYY (hh:mm:ss)").format("x");
        var $currentTime = new Date();

        if(parseInt($dateSet) >= $currentTime.getTime()){
            return true;
        }

        return false;
    };

    $(document).ready(function(){

        var $notificationModal = $(".modal-send-notification");
        var $responseErrorBox = $(".notification-errors");
        var $targetParameterContainer = $(".target-parameter-container");
        var $targetTypeDropdown = $notificationModal.find("select[name='targetType']");
        var $targetParameterInput = '<input class="form-ui" name="target" placeholder="Target Parameters" type="text">';
        var $targetParameterSelect =
            '<select name="target" class="form-ui ui search single selection dropdown">' +
                '<option value="flashSale">Flash Sale</option>' +
                '<option value="categories">Categories</option>' +
                '<option value="hotItems">Hot Items</option>' +
                '<option value="newItems">New Items</option>' +
                '<option value="todaysPromo">Todays Promo</option>' +
                '<option value="newStores">New Stores</option>' +
                '<option value="hotStores">Hot Stores</option>' +
                '<option value="dailyLogin">Daily Login</option>' +
            '</select>';

        var $notificationForm = $("form[name='notification']");

        var $formRules = {
            title: {
                identifier  : 'title',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Title is required'
                    },
                    {
                        type   : 'maxLength[50]',
                        prompt : 'Title can only be up to 50 characters'
                    }
                ]
            },
            recipient: {
                identifier  : 'recipient',
                rules: [
                    {
                        type   : 'checkAtleast[1]',
                        prompt : 'Please select atleast one recipient device.'
                    }
                ]
            },
            targetType: {
                identifier  : 'targetType',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please select target'
                    }
                ]
            },
            message: {
                identifier  : 'message',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Message is required'
                    },
                    {
                        type   : 'maxLength[100]',
                        prompt : 'Message can only be up to 100 characters'
                    }
                ]
            },
            dateScheduled: {
                identifier  : 'dateScheduled',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Date scheduled required'
                    },
                    {
                        type   : 'greaterThanNow',
                        prompt : 'Schedule should be ahead of the current date and time.'
                    }
                ]
            },
            isActive: {
                identifier  : 'isActive',
                rules: []
            }
        };

        var $notificationFormSettings = {
            fields: {},
            onSuccess: function(){
                if(!$ajaxLoading){

                    var $recipient = null;
                    var $devices = $notificationForm.form("get value", "recipient");
                    var $targetType = $notificationForm.form("get value", "targetType");

                    var $target = getTargetParameter($targetType);

                    if($devices[0] === true && $devices[1] === true){
                        $recipient = RECIPIENT_ALL;
                    }
                    else if($devices[0] === true && $devices[1] === false){
                        $recipient = RECIPIENT_IOS;
                    }
                    else if($devices[0] === false && $devices[1] === true){
                        $recipient = RECIPIENT_ANDROID;
                    }

                    var $postData = {
                        "title"         : $notificationForm.form("get value", "title"),
                        "message" 		: $notificationForm.form("get value", "message"),
                        "targetType" 	: $targetType,
                        "target" 	    : $target,
                        "dateScheduled" : $notificationForm.form("get value", "dateScheduled"),
                        "recipient"     : $recipient,
                        "isActive"      : $notificationForm.form("get value", "isActive"),
                        "_token"       : $("input[name='_token']").val()
                    };

                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");

                    if($isCreate === false){
                        $postData["deviceNotificationId"] = $deviceNotificationId;
                    }

                    $ajaxLoading = true;

                    $.ajax({
                        url: Routing.generate($isCreate? "backend_push_notification_create" : "backend_push_notification_update"),
                        type: 'POST',
                        data: $postData,
                        success: function($response) {

                            var $data = $response.data;
                            var $row, $isSent, $isActive, $clone = $(".notification-clone").clone();

                            if($response.isSuccessful){

                                $isSent = $data.isSent? "Sent" : "Waiting";
                                $isActive = $data.isActive? "true" : "false";

                                $targetTypeString = getTargetTypeString($targetType);

                                $row = "" +
                                "<td>" + getRecipient(parseInt($data.recipient)) + "</td>" +
                                "<td>" + $data.title + "</td>" +
                                "<td>" + $data.message + "</td>" +
                                "<td>" + $targetTypeString + "</td>" +
                                "<td>" + $data.createdBy + "</td>" +
                                "<td>" +
                                    moment($data.dateScheduled, "MM/DD/YYYY hh:mm:ss").format("MM/DD/YYYY") +
                                "<br />" +
                                    moment($data.dateScheduled, "MM/DD/YYYY hh:mm:ss").format("hh:mm:ss") +
                                "</td>" +
                                "<td>" + $isSent + "</td>" +
                                "<td>" + $isActive + "</td>";

                                if($isCreate === true){

                                    $clone.removeClass("notification-clone hidden");

                                    $clone.attr("data-id", $data.deviceNotificationId)
                                          .attr("data-details", JSON.stringify($data))
                                          .html($.parseHTML($row));

                                    $(".notification-list tbody").prepend($clone);
                                }
                                else{

                                    $tr = $("tr[data-id='" + $data.deviceNotificationId + "']");

                                    $tr.attr("data-details", JSON.stringify($data))
                                       .html($.parseHTML($row));
                                }

                                $notificationModal.modal("hide");
                            }
                        },
                        complete: function(){
                            $ajaxLoading = false;
                        },
                        error: function($response){

                            var $responseJson = $response.responseJSON;
                            var $errors = $responseJson.data.errors;
                            var $errorList = "<ul>";

                            $errors.forEach(function(value){
                                $errorList += "<li>" + value + "</li>"
                            });

                            $errorList += "</ul>";

                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        }
                    });
                }

                return false;
            }
        };

        $notificationFormSettings.fields = $formRules;

        $notificationForm.form($notificationFormSettings);

        $(".dropdown").dropdown();
        $(".ui.checkbox").checkbox();

        $('.datetimepicker').datetimepicker({
            format: "MM/DD/YYYY (HH:mm:ss)"
        });

        $(document).on("click", ".notification", function(){

            var $this = $(this);

            var $details = JSON.parse($this.attr("data-details"));

            var $dateScheduled = moment($details.dateScheduled, "MM/DD/YYYY HH:mm:ss").format("MM/DD/YYYY (HH:mm:ss)");

            $(".checkbox").checkbox("uncheck");
            $(".with-close-message").html("").addClass("hidden");
            $isCreate = false;
            $deviceNotificationId = $details.deviceNotificationId;

            $notificationModal.find("input[name='title']").val($details.title);
            $notificationModal.find("input[name='dateScheduled']").val($dateScheduled);
            $notificationModal.find("textarea[name='message']").val($details.message);

            $targetTypeDropdown.dropdown("set selected", $details.targetType);

            renderTargetParameter($details.targetType);
            checkRecipients($details.recipient);

            switch($details.targetType){
                case TARGET_TYPE_PRODUCT:
                    var $route = $frontendHostName +
                        Routing.generate("frontend_product_route") +
                        "/" + $details.product.slug;

                    $("input[name='target']").val($route);
                break;
                case TARGET_TYPE_PRODUCT_LIST:
                    var $route = $frontendHostName +
                        Routing.generate("frontend_search_product_route") +
                        "?" + $details.targetParameters;

                    $("input[name='target']").val($route);
                break;
                case TARGET_TYPE_STORE:
                    var $route = $frontendHostName +
                        Routing.generate("frontend_seller_route") +
                        $details.store.storeSlug;

                    $("input[name='target']").val($route);
                break;
                case TARGET_TYPE_STORE_LIST:
                    var $route = $frontendHostName +
                        Routing.generate("frontend_search_seller_route") +
                        "?" + $details.targetParameters;

                    $("input[name='target']").val($route);
                break;
            }

            if($details.isActive === true){
                $("[data-type='is-active']").checkbox("set checked");
            }

            $notificationModal.modal("show");
        });

        $(document).on("change", "select[name='targetType']", function(){

            var $this = $(this);
            var $currentTargetType = $this.val();
            renderTargetParameter($this.val());
        });

        $(".modal-send-notification-trigger").click(function(){

            $(".with-close-message").html("").addClass("hidden");
            $isCreate = true;

            $notificationModal.find("input[name='title']").val("");
            $notificationModal.find("input[name='dateScheduled']").val("");
            $notificationModal.find("textarea[name='message']").val("");
            $targetTypeDropdown.dropdown("set selected", TARGET_TYPE_HOME);

            $(".checkbox").checkbox("uncheck");

            renderTargetParameter(TARGET_TYPE_HOME);

            $notificationModal.modal("show");
        });

        function checkRecipients($recipient){

            switch(parseInt($recipient)){
                case RECIPIENT_ANDROID:
                    $("[data-type='android']").checkbox("set checked");
                break;
                case RECIPIENT_IOS:
                    $("[data-type='ios']").checkbox("set checked");
                break;
                default:
                    $("[data-type='android']").checkbox("set checked");
                    $("[data-type='ios']").checkbox("set checked");
                break;
            }
        }

        function getRecipient($recipient){

            switch($recipient){
                case RECIPIENT_ANDROID:
                    return "Android"
                break;
                case RECIPIENT_IOS:
                    return "IOS";
                break;
                default:
                    return "Android, IOS";
                break;
            }
        }

        function renderTargetParameter($targetType){

            $(".target-parameter").show();

            $targetParameterContainer.html("");
            switch($targetType){
                case TARGET_TYPE_WEBVIEW:
                    $targetParameterContainer.html($targetParameterSelect);
                    $("select[name='target']").dropdown();
                break;
                case TARGET_TYPE_PRODUCT:
                case TARGET_TYPE_PRODUCT_LIST:
                case TARGET_TYPE_STORE:
                case TARGET_TYPE_STORE_LIST:
                    $targetParameterContainer.html($targetParameterInput);
                break;
                default:
                    $(".target-parameter").hide();
                break;
            }
        }

        function getTargetParameter($targetType){

            switch($targetType){
                case TARGET_TYPE_WEBVIEW:
                    return $("select[name='target']").val();
                break;
                case TARGET_TYPE_PRODUCT:
                case TARGET_TYPE_PRODUCT_LIST:
                case TARGET_TYPE_STORE:
                case TARGET_TYPE_STORE_LIST:
                    return $("input[name='target']").val();
                break;
            }
        }

        function getTargetTypeString($targetType){

            switch($targetType){
                case TARGET_TYPE_WEBVIEW:
                    return "Custom Pages";
                case TARGET_TYPE_PRODUCT:
                    return "Product";
                case TARGET_TYPE_PRODUCT_LIST:
                    return "Product Search";
                case TARGET_TYPE_STORE:
                    return "Store";
                case TARGET_TYPE_STORE_LIST:
                    return "Store Search";
                default:
                    return "Home";
            }
        }
    });

})(jQuery);
