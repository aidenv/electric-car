(function($) {
    var IMAGE_TYPE_AVATAR = 0;
    var IMAGE_TYPE_BANNER = 1;
    var $currentTime, $timeLimit, $timeInterval;
    var $ajaxLoading = false;
    var $validFileTypes = ["image/jpeg", "image/jpg", "image/png"];
    var $copyReferralCodeTrigger = $("#copy-referral-code-trigger");
    var client = new ZeroClipboard($copyReferralCodeTrigger, {
        moviePath: "/assets/js/bower/ZeroClipboard.swf"
    });
    var $userContactNumber = null;
    var $loadedCategoryIds = [];

    window.fbAsyncInit = function() {
        FB.init({
            appId      : $(".share-with-facebook").data("app-id"),
            xfbml      : true,
            version    : 'v2.4'
        });
    };

    (function(d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    client.on( "load", function(client) {
      client.on( "complete", function(client, args) {
        // `this` is the element that was clicked
        this.style.display = "none";
      });
    });

    $copyReferralCodeTrigger.click(function(){
        $(".copied-referral-code-modal").modal("show");
    });

    $(".copied-referral-code-modal .confirm").click(function(){
        $(".copied-referral-code-modal").modal("hide");
    });

    $(".edit-mobile-phone-number-modal-trigger").click(function(){
        $(".edit-mobile-phone-number-modal").modal("show").modal({
            onShow : function(){
                $("input[name='newContactNumber']").val("");
            }
        });
    });

    $(document).ready(function(){

        var $emailPattern = /^[\w\.\-+]+@([\w\-]+\.)+[a-zA-Z]+$/;
        var $changeContactNumberForm = $("form[name='change-contact-number']");
        var $verifyContactNumberForm = $("form[name='verify-contact-number']");
        var $shareViaEmailForm = $("form[name='share-via-email']");
        var $changeProfilePhotoInput = $(".change-profile-photo");
        var $imageCropperData = {};
        var $coverCropperData = {};
        var $storeType = $(".edit-verify-number-modal").data("store-type");
        var $image = $("#profile-photo-modal .cropper-profile-photo img"), profileCropBoxData, profileCanvasData;
        var $cover = $("#cover-photo-modal .cropper-cover-photo img"), coverCropBoxData, coverCanvasData;
        var $oldContactNumberIsVerified = $(".unverified-mobile-number").hasClass("hidden");

        var $requestType = 'change-contact-number';
        var $requestContactNumber = $changeContactNumberForm.form("get value", "oldContactNumber");

        var $changeContactNumberFormRules =  {
            fields : {
                newContactNumber: {
                    identifier  : 'newContactNumber',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'New contact number is required.'
                        },
                        {
                            type   : 'maxLength[20]',
                            prompt : 'Contact number can only have a maximum of 20 characters'
                        }
                    ]
                }
            },
            onSuccess : function(){

                if(!$ajaxLoading){

                    var $oldContactNumber = $changeContactNumberForm.form("get value", "oldContactNumber");
                    var $newContactNumber = $changeContactNumberForm.form("get value", "newContactNumber");
                    var $responseErrorBox = $(".change-contact-number-errors");
                    var $verifyErrorBox = $(".verify-contact-number-errors");
                    var $postData = {
                        oldContactNumber   : $oldContactNumber,
                        newContactNumber   : $newContactNumber,
                        storeType          : $storeType
                    };

                    $ajaxLoading = true;
                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");
                    $verifyErrorBox.addClass("hidden");
                    $verifyErrorBox.html("");

                    $.ajax({
                        url: Routing.generate('core_change_contact_number_request'),
                        type: 'POST',
                        data: $postData,
                        beforeSend: function(){
                            $oldContactNumberIsVerified = $(".unverified-mobile-number").hasClass("hidden");
                            applyLoading($changeContactNumberForm);
                        },
                        success: function(response) {
                            $(".verified-mobile-number").addClass("hidden");
                            $(".unverified-mobile-number").removeClass("hidden");

                            $userContactNumber = $newContactNumber;
                            $(".edit-verify-number-modal").modal({
                                onShow : function(){

                                    $requestType = 'change-contact-number';
                                    $requestContactNumber = $changeContactNumberForm.form("get value", "newContactNumber");

                                    $currentTime = Math.floor(moment().format('X'));
                                    $timeLimit = moment().add(60, 'minutes').format('X');
                                    $('#time-left').text("59:59");

                                    $timeInterval = setInterval(function(){
                                        $currentTime += 1;
                                        if($currentTime > $timeLimit){
                                        $verifyErrorBox.removeClass("hidden");
                                        $verifyErrorBox.html("<ul><li>Session has expired.</li></ul>");

                                            $('#time-left').text("0:00");
                                            $verifyContactNumberForm.find("button[type='submit']").attr("disabled", true);
                                            clearInterval($timeInterval);
                                        }
                                        else{
                                            $('#time-left').text(getTimeDiff($timeLimit));
                                        }
                                    }, 1000);
                                },
                                onHidden : function(){
                                    clearInterval($timeInterval);
                                }
                            }).modal("show").modal({
                                onHide: function(){
                                    if($oldContactNumberIsVerified){
                                        $(".unverified-mobile-number").addClass("hidden");
                                        $(".verified-mobile-number").removeClass("hidden");
                                    }
                                    else{
                                        $(".unverified-mobile-number").removeClass("hidden");
                                        $(".verified-mobile-number").addClass("hidden");
                                    }
                                }
                            });

                            return true;
                        },
                        error: function(response){
                            var $responseJson = response.responseJSON;
                            var $errors = $responseJson.data.errors;
                            var $errorList = "<ul>";

                            $errors.forEach(function(value){
                                $errorList += "<li>" + value + "</li>"
                            });

                            $errorList += "</ul>";

                            if($errors.length > 0){
                                $responseErrorBox.html($errorList);
                                $responseErrorBox.removeClass("hidden");
                            }
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($changeContactNumberForm);
                        }
                    });

                    return false;
                }
            }
        };

        var $verifyContactNumberFormRules =  {
            fields : {
                code: {
                    identifier  : 'code',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[60]',
                            prompt : 'Please enter at most 60 characters'
                        }
                    ]
                }
            },
            onSuccess : function(){

                if(!$ajaxLoading){
                    $ajaxLoading = true;

                    var $verificationCode = $verifyContactNumberForm.form("get value", "code");
                    var $verifyErrorBox = $(".verify-contact-number-errors");

                    $verifyErrorBox.addClass("hidden");
                    $verifyErrorBox.html("");

                    $.ajax({
                        url: Routing.generate('core_validate_token'),
                        type: 'POST',
                        data: {
                            verificationCode : $verificationCode,
                            type : $requestType,
                            storeType : $storeType,
                            contactNumber : $requestContactNumber
                        },
                        beforeSend: function(){
                            applyLoading($verifyContactNumberForm);
                        },
                        success: function(response) {
                            $(".verified-mobile-number").show();
                            $(".unverified-mobile-number").hide();
                            $(".success-change-contact-number-modal").modal("show");

                            $("input[name='contactNumber']").val($requestContactNumber);
                            $("input[name='oldContactNumber']").val($requestContactNumber);
                            $changeContactNumberForm.find("input[name='oldPassword']").form("set value", $requestContactNumber);

                            return true;
                        },
                        error: function(response){
                            var $responseJson = response.responseJSON;
                            var $errorList = "<ul><li>" + $responseJson.message + "</li></ul>";

                            $verifyErrorBox.html($errorList);
                            $verifyErrorBox.removeClass("hidden");
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($verifyContactNumberForm);
                        }
                    });

                    return false;
                }
            }
        };


        var $shareViaEmailFormRules =  {
            fields: {
                recipient: {
                    identifier  : 'recipient',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        }
                    ]
                },
                message: {
                    identifier  : 'message',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        },
                        {
                            type   : 'maxLength[255]',
                            prompt : 'Please enter at most 250 characters'
                        }
                    ]
                }
            },
            onSuccess: function(){

                var $recipients = $("input[name='recipient']").val().split(",");

                $hasError = false;

                $recipients.forEach(function(email){
                    if(!$emailPattern.test(email.trim())){
                        $(".share-via-email-errors").html("Invalid email address").removeClass("hidden");
                        $hasError = true;
                    }
                });

                if(!$hasError){
                    $.ajax({
                        url: Routing.generate('user_share_via_email'),
                        type: 'POST',
                        data: $shareViaEmailForm.serialize(),
                        success: function(response) {
                            $("input[name='recipient']").val("");
                            $("textarea[name='message']").val("");
                            $(".success-share-email-modal").modal("show");
                        }
                    });
                }

                return false;
            }
        };


        $('input[name=store-category-id]:checked').each(function() {$loadedCategoryIds.push($(this).val());});

        $('.request-verify-mobile').click(function(){
            if(!$ajaxLoading){
                var $verifyErrorBox = $(".verify-contact-number-errors");
                $ajaxLoading = true;

                $verifyErrorBox.addClass("hidden");
                $verifyErrorBox.html("");

                $.ajax({
                    url: Routing.generate('core_resend_sms_verification'),
                    success: function(response) {
                        if(response.isSuccessful){
                            $(".verified-mobile-number").addClass("hidden");
                            $(".unverified-mobile-number").removeClass("hidden");

                            $(".edit-verify-number-modal").modal({
                                onShow : function(){

                                    $requestType = 'verify_contact_number';
                                    $requestContactNumber =  $changeContactNumberForm.form("get value", "oldContactNumber");

                                    $currentTime = Math.floor(moment().format('X'));
                                    $timeLimit = moment().add(60, 'minutes').format('X');
                                    $('#time-left').text("59:59");

                                    $timeInterval = setInterval(function(){
                                        $currentTime += 1;
                                        if($currentTime > $timeLimit){
                                        $verifyErrorBox.removeClass("hidden");
                                        $verifyErrorBox.html("<ul><li>Session has expired.</li></ul>");

                                            $('#time-left').text("0:00");
                                            $verifyContactNumberForm.find("button[type='submit']").attr("disabled", true);
                                            clearInterval($timeInterval);
                                        }
                                        else{
                                            $('#time-left').text(getTimeDiff($timeLimit));
                                        }
                                    }, 1000);
                                },
                                onHidden : function(){
                                    clearInterval($timeInterval);
                                }
                            }).modal("show");
                        }
                        $ajaxLoading = false;
                    },
                    error: function(response){
                        $ajaxLoading = false;
                    }
                });
            }
        });

        $shareViaEmailForm.form($shareViaEmailFormRules);
        $changeContactNumberForm.form($changeContactNumberFormRules);
        $verifyContactNumberForm.form($verifyContactNumberFormRules);

        $(".reset-store-info").click(function(){
            $("input[name='storeName']").val($("input[name='storeName']").attr("data-reset"));
            $("input[name='storeSlug']").val($("input[name='storeSlug']").attr("data-reset"));
            $("textarea[name='storeDescription']").val($("textarea[name='storeDescription']").attr("data-reset"));
        });

        $(document).on("click", ".change-profile-photo", function(e){
            $(".profile-file-input").trigger("click");
        });

        $(document).on("click", ".change-cover-photo", function(e){
            $(".cover-file-input").trigger("click");
        });

        $(document).on("change", ".profile-file-input", function (e){
            var cropImage = $('.information-container .cropper-profile-photo > img');

            var imageFile = this.files[0];
            var browserWindow = window.URL || window.webkitURL;
            var filename =  imageFile.name;
            var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            var objectUrl = browserWindow.createObjectURL(imageFile);

            if($.inArray(imageFile.type, $validFileTypes) != -1){

                var $promise = createImage(objectUrl);

                $promise.done(function($object){

                    var $dataUri = resize($object, false);

                    $image.attr('src', $dataUri);
                    $image.cropper({
                        aspectRatio: 1 / 1,
                        autoCropArea: 1,
                        strict: true,
                        minContainerHeight: 350,
                        maxContainerHeight: 350,
                        minContainerWidth: 350,
                        maxContainerWidth: 350,
                        minCropBoxWidth: 300,
                        maxCropBoxWidth: 300,
                        minCropBoxHeight: 300,
                        maxCropBoxHeight: 300,
                        guides: false,
                        highlight: false,
                        dragCrop: false,
                        cropBoxMovable: false,
                        cropBoxResizable: false,
                        responsive: false,
                        built: function () {
                            $image.cropper('crop');
                            $image.cropper("destroy");
                            $("#profile-photo-modal").modal('show');
                        }
                    });
                });
            }
            else{
                $(".invalid-file-type").modal("show");
            }
        });

        $('#profile-photo-modal').modal({
            observeChanges: true,
            onShow : function(){
                $(".change-profile-photo-errors").addClass("hidden").html("");
                $image.cropper({
                    aspectRatio: 1 / 1,
                    autoCropArea: 1,
                    strict: true,
                    minContainerHeight: 350,
                    maxContainerHeight: 350,
                    minContainerWidth: 350,
                    maxContainerWidth: 350,
                    minCropBoxWidth: 300,
                    maxCropBoxWidth: 300,
                    minCropBoxHeight: 300,
                    maxCropBoxHeight: 300,
                    guides: false,
                    highlight: false,
                    dragCrop: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    responsive: false,
                    built: function () {
                        // Strict mode: set crop box data first
                        $image.cropper('setCropBoxData', profileCropBoxData);
                        $image.cropper('setCanvasData', profileCanvasData);
                    },
                    crop: function(e){
                        $imageCropperData = {
                            x : e.x,
                            y : e.y,
                            width : e.width,
                            height : e.height,
                            rotate : e.rotate,
                            scaleX : e.scaleX,
                            scaleY : e.scaleY
                        };
                    }
                });
            },
            onHidden : function(){
                $image.cropper("destroy");
                $(".profile-file-input").val("");
            }
        });

        $(".upload-cropped-profile-image").click(function(){
            var $button = $(this);
            var $formData = new FormData();
            var $responseErrorBox = $(".change-profile-photo-errors");

            var $dataUri = $image.attr("src");

            $formData.append("image", dataURItoBlob($dataUri), $("input[name='profilePhoto']")[0].files[0].name);
            $formData.append("imageType", IMAGE_TYPE_AVATAR);
            $formData.append("x", $imageCropperData.x);
            $formData.append("y", $imageCropperData.y);
            $formData.append("width", $imageCropperData.width);
            $formData.append("height", $imageCropperData.height);
            $formData.append("resizeWidth", 350);
            $formData.append("resizeHeight", 350);

            $.ajax({
                url: Routing.generate('core_upload_user_photo'),
                type: 'POST',
                data: $formData,
                processData: false,
                contentType: false,
                beforeSend: function(){
                    $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function(response) {
                    if(response.isSuccessful){
                        $image.cropper("destroy");
                        $(".image-holder.image-profile > img").attr("src", response.data.mediumUrl);
                        $(".user-image-container > img").attr("src", response.data.thumbnailUrl);
                        $("#profile-photo-modal").modal('hide');
                    }
                },
                error: function(response){
                    var $responseJson = response.responseJSON;
                    var $errors = $responseJson.data.errors;
                    var $errorList = "<ul>";

                    $errors.forEach(function(value){
                        $errorList += "<li>" + value + "</li>"
                    });

                    $errorList += "</ul>";

                    $responseErrorBox.html($errorList);
                    $responseErrorBox.removeClass("hidden");
                },
                complete: function(){
                    $button.html("Crop").removeClass('disabled');
                }
            });
        });

        //cover
        $(document).on("change", ".cover-file-input", function (e){
            var cropImage = $('.information-container .cropper-cover-photo > img');

            var imageFile = this.files[0];
            var browserWindow = window.URL || window.webkitURL;
            var filename =  imageFile.name;
            var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            var objectUrl = browserWindow.createObjectURL(imageFile);

            if($.inArray(imageFile.type, $validFileTypes) != -1){

                var $promise = createImage(objectUrl);

                $promise.done(function($object){

                    var $dataUri = resize($object, true);

                    $cover.attr('src', $dataUri);
                    $cover.cropper({
                        aspectRatio: 18/5,
                        autoCropArea: 1,
                        strict: true,
                        minContainerHeight: 400,
                        maxContainerHeight: 400,
                        minContainerWidth: 400,
                        maxContainerWidth: 400,
                        minCropBoxWidth: 400,
                        maxCropBoxWidth: 400,
                        minCropBoxHeight: 400,
                        maxCropBoxHeight: 400,
                        guides: false,
                        highlight: false,
                        dragCrop: false,
                        cropBoxMovable: false,
                        cropBoxResizable: false,
                        responsive: false,
                        built: function () {
                            $cover.cropper('crop');
                            $cover.cropper("destroy");
                            $("#cover-photo-modal").modal('show');
                        }
                    });
                });
            }
            else{
                $(".invalid-file-type").modal("show");
            }
        });

        $('#cover-photo-modal').modal({
            observeChanges: true,
            onShow : function(){
                $(".change-cover-photo-errors").addClass("hidden").html("");
                $cover.cropper({
                    aspectRatio: 18/5,
                    autoCropArea: 1,
                    strict: true,
                    minContainerHeight: 400,
                    maxContainerHeight: 400,
                    minContainerWidth: 400,
                    maxContainerWidth: 400,
                    minCropBoxWidth: 400,
                    maxCropBoxWidth: 400,
                    minCropBoxHeight: 400,
                    maxCropBoxHeight: 400,
                    guides: false,
                    highlight: false,
                    dragCrop: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    responsive: false,
                    built: function () {
                        // Strict mode: set crop box data first
                        $cover.cropper('setCropBoxData', profileCropBoxData);
                        $cover.cropper('setCanvasData', profileCanvasData);
                    },
                    crop: function(e){
                        $coverCropperData = {
                            x : e.x,
                            y : e.y,
                            width : e.width,
                            height : e.height,
                            rotate : e.rotate,
                            scaleX : e.scaleX,
                            scaleY : e.scaleY
                        };
                    }
                });
            },
            onHidden : function(){
                $cover.cropper("destroy");
                $(".cover-file-input").val("");
            }
        });

        $(".upload-cropped-cover-image").click(function(){
            var $button = $(this);
            var $formData = new FormData();
            var $responseErrorBox = $(".change-cover-photo-errors");

            var $dataUri = $cover.attr("src");

            $formData.append("image", dataURItoBlob($dataUri), $("input[name='coverPhoto']")[0].files[0].name);
            $formData.append("imageType", IMAGE_TYPE_BANNER);
            $formData.append("x", $coverCropperData.x);
            $formData.append("y", $coverCropperData.y);
            $formData.append("width", $coverCropperData.width);
            $formData.append("height", $coverCropperData.height);
            $formData.append("resizeWidth", 2300);
            $formData.append("resizeHeight", 600);

            $.ajax({
                url: Routing.generate('core_upload_user_photo'),
                type: 'POST',
                data: $formData,
                processData: false,
                contentType: false,
                type: 'POST',
                beforeSend: function(){
                    $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function(response) {
                    if(response.isSuccessful){
                        $cover.cropper("destroy");
                        $(".image-holder.image-cover > img").attr("src", response.data.thumbnailUrl);
                        $("#cover-photo-modal").modal('hide');
                    }
                },
                error: function(response){
                    var $responseJson = response.responseJSON;
                    var $errors = $responseJson.data.errors;
                    var $errorList = "<ul>";

                    $errors.forEach(function(value){
                        $errorList += "<li>" + value + "</li>"
                    });

                    $errorList += "</ul>";

                    $responseErrorBox.html($errorList);
                    $responseErrorBox.removeClass("hidden");
                },
                complete: function(){
                    $button.html("Crop").removeClass('disabled');
                }
            });
        });

        $(".share-with-facebook").click(function(){
            FB.ui({
              method: 'share',
              href: $("#qrcode").data("slug"),
            }, function(response){});
        });

        $(document).on("keyup", "input[name='storeSlug']", function(){
            var $storeName = $(this).val();
            var $slugLabel = $(".slug-label");
            var $storePrefix = $slugLabel.data("link");

            $slugLabel.text("Store Link (" + $storePrefix + $storeName + ")");
        });

        $(".save-store-info").click(function(){
            if(!$ajaxLoading){
                $ajaxLoading = true;

                var $button = $(this);
                var $responseErrorBox = $(".update-store-info-errors");
                var $slugChanged = $("input[name='storeSlug'][disabled]");
                var $oldSlug = $("input[name='storeSlug']").data("slug");

                var $data = {
                    storeName : $("input[name='storeName']").val(),
                    storeDescription : $("textarea[name='storeDescription']").val()
                };

                if($slugChanged.length === 0){
                    $data.storeSlug = $("input[name='storeSlug']").val();
                }


                $responseErrorBox.addClass("hidden").html("");

                $.ajax({
                    url: Routing.generate('user_update_info'),
                    type: 'POST',
                    data: $data,
                    beforeSend: function(){
                        $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                    },
                    success: function(response) {
                        if(response.isSuccessful){
                            var $slugLink = $(".slug-link");
                            var $baseUri = $slugLink.data("base");

                            $responseErrorBox.addClass("hidden").html("");
                            $(".success-update-store-info-message").modal('show');

                            if($slugChanged.length === 0 && $oldSlug != $("input[name='storeSlug']").val().trim()){
                                $("input[name='storeSlug']").prop("disabled", true);
                            }

                            $slugLink.attr("href", $baseUri + response.data.storeSlug);
                            $("img#qrcode").attr("src", response.data.qrCodeLocation);
                            $("input[name='storeName']").attr("data-reset", response.data.storeName);
                            $("input[name='storeSlug']").attr("data-reset", response.data.storeSlug);
                            $("textarea[name='storeDescription']").attr("data-reset", response.data.storeDescription);
                        }
                    },
                    error: function(response){
                        var $responseJson = response.responseJSON;
                        var $errors = $responseJson.data.errors;
                        var $errorList = "<ul>";

                        $errors.forEach(function(value){
                            $errorList += "<li>" + value + "</li>"
                        });

                        $errorList += "</ul>";

                        if($errors.length > 0){
                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        }
                    },
                    complete: function(){
                        $ajaxLoading = false;
                        $button.html("Save Changes").removeClass('disabled');
                    }
                });
            }
        });

        $(document).on('click', '#btn-reset-store-categories', function () {
            $('input[name=store-category-id]').attr('checked', false);

            $loadedCategoryIds.forEach(function($categoryId){
                var $categoryInput = $("input[name='store-category-id'][value='" + $categoryId + "']");
                $categoryInput.trigger("click");
            });
        });

        $(document).on('click', '#btn-submit-store-categories', function () {
            var $messageModal = $('#modal-message');
            var $this = $(this);
            var selectedStoreCategoryIds = [];
            var $storeCategoryIds = $('input[name=store-category-id]:checked').each(function() {selectedStoreCategoryIds.push($(this).val());});

            if (selectedStoreCategoryIds.length === 0) {
                $messageModal.find('.header-content').html('Select at least one (1) Product Category.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            $.ajax({
                url: Routing.generate('user_store_information_submit_store_categories'),
                method: 'post',
                dataType: 'json',
                data: {
                    selectedStoreCategoryIds: selectedStoreCategoryIds
                },
                beforeSend: function () {
                    $this.attr('disabled', true);
                    $this.find('.text').addClass('hidden');
                    $this.find('.loader').removeClass('hidden');
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find('.text').removeClass('hidden');
                    $this.find('.loader').addClass('hidden');

                    if (response.isSuccessful) {

                        $loadedCategoryIds = selectedStoreCategoryIds;
                        $messageModal.find('.header-content').html('Successfully Saved Changes.');
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }
                    else {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }

                }
            });
        });

        $(document).on('click', '#save-referrer', function () {
            var $messageModal = $('#modal-message');
            var $this = $(this);
            var referrerCode = $('#txt-referrer-code').val().trim();

            $.ajax({
                url        : Routing.generate('user_update_referrer'),
                method     : 'post',
                type       : 'json',
                data       : {
                    referrerCode: referrerCode
                },
                beforeSend : function () {
                    $this.attr('disabled', true);
                },
                success     : function (response) {
                    $this.attr('disabled', false);

                    if (response.isSuccessful === true) {
                        $this.hide();
                    }

                    $messageModal.find('.header-content').html(response.message);
                    $messageModal.find('.sub-header-content').html('');
                    $messageModal.modal('show');

                }
            });
        });

    });

    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }

    function getTimeDiff(codeExpiration){
        var date = new Date();
        var timeNow = Math.floor(date.getTime() / 1000);
        var unixTimeDiff = codeExpiration - timeNow;
        var minutes = Math.floor(unixTimeDiff / 60);
        var seconds = unixTimeDiff - minutes * 60;

        if(seconds < 10){
            seconds = "0" + seconds;
        }

        return minutes + ":" + seconds;
    }

    function createImage (src) {
        var deferred = $.Deferred();
        var img = new Image();

        img.onload = function() {
            $(img).attr("naturalWidth", this.naturalWidth);
            $(img).attr("naturalHeight", this.naturalHeight);

            deferred.resolve(img);
        };

        img.src = src;
        return deferred.promise();
    };

    function resize (image, isCover) {

        var $height = parseInt($(image).attr("naturalheight"));
        var $width = parseInt($(image).attr("naturalwidth"));
        var $maxSize = isCover? 2300 : 1280;

        mainCanvas = document.createElement("canvas");

        if($width > $height){
            var $multiplier = 1 - (($width/$height)%1);
            mainCanvas.width = $maxSize;
            mainCanvas.height = $maxSize * $multiplier;
        }
        else{
            var $multiplier = 1 - (($height/$width)%1);
            mainCanvas.height = $maxSize;
            mainCanvas.width = $maxSize * $multiplier;
        }

        var ctx = mainCanvas.getContext("2d");

        ctx.drawImage(image, 0, 0, mainCanvas.width, mainCanvas.height);

        return mainCanvas.toDataURL("image/jpeg");
    };

    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }

    $(".share-email-trigger").on("click", function(){
        $(".share-email-modal").modal("show");

        $('.coupled').modal({
            allowMultiple: false
        });

        $(".share-via-email-errors").html("").addClass("hidden");
    });

    $(".show-prev-remarks").on("click", function() {
        $(".prev-remarks").slideToggle({direction: "up" }, 400);
        $(this).toggleClass("show-txt-remarks");
    });

    $(".edit-email-trigger").click(function(){
        $(".edit-email-modal").modal("show");
    });

    $(".submit-to-success").click(function(){
        $(".success-resend-email-verification").modal("show");
    });

    $("input[name='storeName']").slugify($("input[name='storeSlug']"));
;
})(jQuery);
